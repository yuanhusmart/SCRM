<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\OtsSuiteWorkWechatChatData;
use common\models\SuiteCorpChatAgree;
use common\models\SuiteCorpExternalContact;
use common\models\SuiteCorpExternalContactFollowUser;
use common\models\SuiteCorpExternalContactFollowUserTags;
use common\models\SuiteCorpGroupChat;
use common\models\SuiteCorpGroupChatMember;
use common\models\SuiteCorpSessions;
use common\models\SuiteCorpSessionsMember;

/**
 * Class SuiteCorpChatAgreeService
 * @package common\services
 */
class SuiteCorpChatAgreeService extends Service
{

    /**
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     * @throws \yii\db\Exception
     */
    public static function create($params)
    {
        $attributes = self::includeKeys($params, SuiteCorpChatAgree::CHANGE_FIELDS);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $create = SuiteCorpChatAgree::find()->where(['suite_id' => $params['suite_id'], 'corp_id' => $params['corp_id'], 'session_id' => $params['session_id'], 'msgid' => $params['msgid']])->one();
            if (empty($create)) {
                $create = new SuiteCorpChatAgree();
            }
            $create->load($attributes, '');
            //校验参数
            if (!$create->validate()) {
                throw new ErrException(Code::PARAMS_ERROR, $create->getErrors());
            }
            if (!$create->save()) {
                throw new ErrException(Code::CREATE_ERROR, $create->getErrors());
            }

            // 如果是群组消息 则进行群组人员数据修改
            if (!empty($params['chatid'])) {
                $chatMember = SuiteCorpGroupChat::find()
                                                ->select(['gcm.id', 'gcm.chat_agree'])
                                                ->alias('gc')
                                                ->innerJoin(SuiteCorpGroupChatMember::tableName() . ' AS gcm', 'gc.id = gcm.group_chat_id')
                                                ->andWhere([
                                                    'gc.suite_id'   => $params['suite_id'],
                                                    'gc.corp_id'    => $params['corp_id'],
                                                    'gc.chat_id'    => $params['chatid'],
                                                    'gc.group_type' => SuiteCorpGroupChat::GROUP_TYPE_1
                                                ])
                                                ->andWhere([
                                                    'gcm.userid' => $params['sender_id'],
                                                    'gcm.type'   => $params['sender_type']
                                                ])
                                                ->limit(1)
                                                ->asArray()
                                                ->one();

                if ($chatMember) {
                    if ($params['msgtype'] == OtsSuiteWorkWechatChatData::MSG_TYPE_24) {
                        $chatAgree = SuiteCorpGroupChatMember::CHAT_AGREE_1;
                    } elseif ($params['msgtype'] == OtsSuiteWorkWechatChatData::MSG_TYPE_25) {
                        $chatAgree = SuiteCorpGroupChatMember::CHAT_AGREE_2;
                    }
                    if (!empty($chatAgree) && $chatMember['chat_agree'] != $chatAgree) {
                        SuiteCorpGroupChatMemberService::update(['id' => $chatMember['id'], 'chat_agree' => $chatAgree]);
                    }
                }
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        return $create->getPrimaryKey();
    }

    /**
     * @param $params
     * @return array
     * @throws ErrException
     */
    public static function items($params)
    {
        list($page, $per_page) = self::getPageInfo($params);
        $suiteId = self::getString($params, 'suite_id');
        $corpId  = self::getString($params, 'corp_id');
        $kind    = self::getInt($params, 'kind');
        if (!$suiteId || !$corpId || !$kind) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        // 类型: 1好友, 2群聊
        if (!in_array($kind, [SuiteCorpSessions::KIND_1, SuiteCorpSessions::KIND_2])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        $query = SuiteCorpChatAgree::find()
                                   ->alias('chat_agree')
                                   ->innerJoin(SuiteCorpSessions::tableName() . ' AS sessions', 'chat_agree.suite_id = sessions.suite_id AND chat_agree.corp_id = sessions.corp_id AND chat_agree.session_id = sessions.session_id')
                                   ->andWhere(["chat_agree.suite_id" => $suiteId])
                                   ->andWhere(["chat_agree.corp_id" => $corpId])
                                   ->andWhere(["sessions.kind" => $kind]);

        // 消息类型
        if ($msgtype = self::getInt($params, 'msgtype')) {
            $query->andWhere(["chat_agree.msgtype" => $msgtype]);
        }

        // 会话ID
        if ($sessionId = self::getString($params, 'session_id')) {
            $query->andWhere(["chat_agree.session_id" => $sessionId]);
        }

        // 消息时间 - 开始
        if ($sendTimeStart = self::getInt($params, 'send_time_start')) {
            $query->andWhere(['>=', 'chat_agree.send_time', $sendTimeStart]);
        }

        // 消息时间 - 截止
        if ($sendTimeEnd = self::getInt($params, 'send_time_end')) {
            $query->andWhere(['<=', 'chat_agree.send_time', $sendTimeEnd]);
        }

        $querySelect = ['chat_agree.*'];

        if ($kind == SuiteCorpSessions::KIND_1) {
            $querySelect[] = 'external_contact_follow_user.add_way';
            $querySelect[] = 'external_contact_follow_user.userid';
            $querySelect[] = 'external_contact.name as external_contact_name';
            $querySelect[] = 'external_contact.corp_name as external_contact_corp_name';
            $querySelect[] = 'external_contact_follow_user.id as external_contact_follow_user_id';

            $query->innerJoin(SuiteCorpSessionsMember::tableName() . ' AS sessions_member', 'sessions.suite_id = sessions_member.suite_id AND sessions.corp_id = sessions_member.corp_id AND sessions.session_id = sessions_member.session_id')
                  ->innerJoin(SuiteCorpExternalContact::tableName() . ' AS external_contact', 'chat_agree.suite_id = external_contact.suite_id AND chat_agree.corp_id = external_contact.corp_id AND chat_agree.sender_id = external_contact.external_userid')
                  ->innerJoin(SuiteCorpExternalContactFollowUser::tableName() . ' AS external_contact_follow_user', 'external_contact.id = external_contact_follow_user.external_contact_id')
                  ->andWhere("external_contact.external_userid=chat_agree.sender_id")
                  ->andWhere("external_contact_follow_user.userid=sessions_member.userid");

            // 该成员添加此客户的来源，具体含义详见来源定义
            $addWay = $params['add_way'] ?? -1;
            if ($addWay != -1) {
                $query->andWhere(['external_contact_follow_user.add_way' => $params['add_way']]);
            }

            // 客户信息
            if ($externalContactName = self::getString($params, 'external_contact_name')) {
                $query->andWhere(['like', 'external_contact.name', $externalContactName]);
            }

            // 添加了此外部联系人的企业成员userid
            if ($followUserIds = self::getArray($params, 'follow_userids')) {
                $query->andWhere(['in', 'external_contact_follow_user.userid', $followUserIds]);
            }

        } else {
            $querySelect[] = 'group_chat.name as group_chat_name';
            $querySelect[] = 'group_chat_member.name as group_chat_member_name';
            $querySelect[] = 'group_chat.owner';
            $querySelect[] = 'group_chat_member.join_scene';
            $query->innerJoin(SuiteCorpGroupChat::tableName() . ' AS group_chat', 'chat_agree.suite_id = group_chat.suite_id AND chat_agree.corp_id = group_chat.corp_id AND chat_agree.session_id = group_chat.chat_id')
                  ->innerJoin(SuiteCorpGroupChatMember::tableName() . ' AS group_chat_member', 'group_chat.id = group_chat_member.group_chat_id')
                  ->andWhere("group_chat_member.userid=chat_agree.sender_id");

            // 入群方式。1 - 由群成员邀请入群（直接邀请入群）2 - 由群成员邀请入群（通过邀请链接入群）3 - 通过扫描群二维码入群
            if ($joinScene = self::getInt($params, 'join_scene')) {
                $query->andWhere(['group_chat_member.join_scene' => $joinScene]);
            }

            // 群名称
            if ($groupChatName = self::getString($params, 'group_chat_name')) {
                $query->andWhere(['like', 'group_chat.name', $groupChatName]);
            }

            // 客户信息
            if ($externalContactName = self::getString($params, 'external_contact_name')) {
                $query->andWhere(['like', 'group_chat_member.name', $externalContactName]);
            }
        }

        $query->select($querySelect);

        $total  = $query->count();
        $offset = ($page - 1) * $per_page;
        $resp   = [];
        if ($total > 0) {
            $resp                        = $query->orderBy(['chat_agree.id' => SORT_DESC])->offset($offset)->limit($per_page)->asArray()->all();
            $externalContactFollowUserId = array_column($resp, 'external_contact_follow_user_id');
            if (!empty($externalContactFollowUserId)) {
                $tags = SuiteCorpExternalContactFollowUserTags::find()
                                                              ->andWhere(['in', 'external_contact_follow_user_id', $externalContactFollowUserId])
                                                              ->asArray()
                                                              ->all();

                foreach ($resp as &$value) {
                    $externalContactTags = [];
                    foreach ($tags as $tag) {
                        if ($value['external_contact_follow_user_id'] == $tag['external_contact_follow_user_id']) {
                            $externalContactTags[] = $tag;
                        }
                    }
                    $value['external_contact_tags'] = $externalContactTags;
                }
            }

        }
        return [
            'ChatAgree'  => $resp,
            'pagination' => [
                'page'     => $page,
                'per_page' => $per_page,
                'total'    => intval($total)
            ]
        ];
    }

}
