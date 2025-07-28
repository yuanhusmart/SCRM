<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\Account;
use common\models\OtsSuiteWorkWechatChatData;
use common\models\SuiteCorpChatAgree;
use common\models\SuiteCorpGroupChat;
use common\models\SuiteCorpGroupChatMember;

/**
 * Class SuiteCorpGroupChatService
 * @package common\services
 */
class SuiteCorpGroupChatService extends Service
{

    // 处理群组名称
    const MQ_GROUP_NAME_EXCHANGE    = 'aaw.group.name.storage.dir.ex';
    const MQ_GROUP_NAME_QUEUE       = 'aaw.group.name.storage.que';
    const MQ_GROUP_NAME_ROUTING_KEY = 'aaw.group.name.storage.rk';

    // 群组补偿
    const MQ_GROUP_COMPENSATE_EXCHANGE    = 'aaw.group.compensate.dir.ex';
    const MQ_GROUP_COMPENSATE_QUEUE       = 'aaw.group.compensate.que';
    const MQ_GROUP_COMPENSATE_ROUTING_KEY = 'aaw.group.compensate.rk';

    // 外部联系人补偿
    const MQ_EXTERNAL_CONTACT_COMPENSATE_EXCHANGE    = 'aaw.external.contact.compensate.dir.ex';
    const MQ_EXTERNAL_CONTACT_COMPENSATE_QUEUE       = 'aaw.external.contact.compensate.que';
    const MQ_EXTERNAL_CONTACT_COMPENSATE_ROUTING_KEY = 'aaw.external.contact.compensate.rk';

    const CHANGE_FIELDS = ['suite_id', 'corp_id', 'chat_id', 'name', 'is_modify', 'create_time', 'owner', 'notice', 'member_version', 'updated_at', 'is_dismiss', 'dismiss_time', 'member_count'];

    /**
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     */
    public static function create($params)
    {
        $attributes = self::includeKeys($params, self::CHANGE_FIELDS);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $chatId                     = $attributes['chat_id'] ?? '';
        $corpId                     = $attributes['corp_id'] ?? '';
        $suiteId                    = $attributes['suite_id'] ?? '';
        $owner                      = $attributes['owner'] ?? ''; //群主
        $memberList                 = empty($params['member_list']) ? [] : $params['member_list'];
        $attributes['member_count'] = count($memberList);
        $transaction                = \Yii::$app->db->beginTransaction();
        $isUpdate                   = true; // 是否更新，true 是 false 否
        $attributes['group_type']   = SuiteCorpGroupChat::GROUP_TYPE_1;
        $attributes['is_modify']    = empty($attributes['name']) ? SuiteCorpGroupChat::IS_MODIFY_1 : SuiteCorpGroupChat::IS_MODIFY_2;
        try {
            $groupChat = SuiteCorpGroupChat::findOne(['suite_id' => $suiteId, 'corp_id' => $corpId, 'chat_id' => $chatId]);
            if (empty($groupChat)) {
                $groupChat = new SuiteCorpGroupChat();
                $isUpdate  = false;
            } else {
                if (!empty($groupChat->name) && empty($attributes['name'])) {
                    $attributes['name'] = $groupChat->name;
                }
            }
            $groupChat->load($attributes, '');
            // 校验参数
            if (!$groupChat->validate()) {
                throw new ErrException(Code::PARAMS_ERROR, $groupChat->getError());
            }
            if (!$groupChat->save()) {
                throw new ErrException(Code::CREATE_ERROR, $groupChat->getError());
            }
            $groupChatId = $groupChat->getPrimaryKey();
            if ($isUpdate === true) {
                SuiteCorpGroupChatMemberService::deleteAll($groupChatId);
            }
            $adminList = empty($params['admin_list']) ? [] : array_column($params['admin_list'], 'userid');
            if ($memberList) {
                foreach ($memberList as $item) {
                    $role = SuiteCorpGroupChatMember::GROUP_CHAT_MEMBER_ROLE_1;
                    if (in_array($item['userid'], $adminList)) {
                        $role = SuiteCorpGroupChatMember::GROUP_CHAT_MEMBER_ROLE_2;
                    }
                    if ($item['userid'] == $owner) {
                        $role = SuiteCorpGroupChatMember::GROUP_CHAT_MEMBER_ROLE_3;
                    }
                    $item['role']           = $role;
                    $item['group_chat_id']  = $groupChatId;
                    $item['invitor_userid'] = $item['invitor']['userid'] ?? '';

                    $chatAgree = SuiteCorpGroupChatMember::CHAT_AGREE_1;
                    // 外部联系人 需要处理 同意存档 1.同意; 2.拒绝
                    if (!empty($item['type']) && $item['type'] == SuiteCorpGroupChatMember::GROUP_CHAT_TYPE_2) {
                        $chatAgreeMsgType = SuiteCorpChatAgree::find()
                            ->select('msgtype')
                            ->where([
                                'suite_id'    => $suiteId,
                                'corp_id'     => $corpId,
                                'session_id'  => $chatId,
                                'sender_id'   => $item['userid'],
                                'sender_type' => $item['type']
                            ])
                            ->orderBy(['send_time' => SORT_DESC, 'id' => SORT_DESC])
                            ->limit(1)
                            ->scalar();
                        // 查询同意存档记录 最后一条数据， 如果拒绝则进行群组成员数据修改
                        if (!empty($chatAgreeMsgType) && $chatAgreeMsgType == OtsSuiteWorkWechatChatData::MSG_TYPE_25) {
                            $chatAgree = SuiteCorpGroupChatMember::CHAT_AGREE_2;
                        }
                    }

                    $item['chat_agree'] = $chatAgree;
                    SuiteCorpGroupChatMemberService::create($item);
                }
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        return $groupChatId;
    }

    /**
     * @param $params
     * @return true
     * @throws ErrException
     */
    public static function updateByChatId($params)
    {
        $corpId  = self::getString($params, 'corp_id');
        $suiteId = self::getString($params, 'suite_id');
        $chatId  = self::getString($params, 'chat_id');
        if (!$suiteId || !$corpId || !$chatId) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data = SuiteCorpGroupChat::findOne(['suite_id' => $suiteId, 'corp_id' => $corpId, 'chat_id' => $chatId]);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        $attributes = self::includeKeys($params, self::CHANGE_FIELDS);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data->attributes = $attributes;
        if (!$data->save()) {
            throw new ErrException(Code::UPDATE_ERROR, $data->getError());
        }
        return true;
    }

    /**
     * 更新群备注
     * @param $params
     * @return true
     * @throws ErrException
     */
    public static function updateNotesByChatId($params)
    {
        $suiteId = self::getString($params, 'suite_id');
        $corpId  = self::getString($params, 'corp_id');
        $chatId  = self::getString($params, 'chat_id');
        $notes   = self::getString($params, 'notes');
        if (!$suiteId || !$corpId || !$chatId || !$notes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data = SuiteCorpGroupChat::find()
            ->andWhere(['suite_id' => $suiteId])
            ->andWhere(['corp_id' => $corpId])
            ->andWhere(['chat_id' => $chatId])
            ->limit(1)
            ->one();
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        $data->notes = $notes;
        if (!$data->save()) {
            throw new ErrException(Code::UPDATE_ERROR, $data->getErrors());
        }
        return true;
    }

    /**
     * @param $params
     * @return true
     * @throws ErrException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public static function delete($params)
    {
        $id = self::getId($params);
        if (empty($id)) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data = SuiteCorpGroupChat::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        if (!$data->delete()) {
            throw new ErrException(Code::DATA_ERROR);
        }
        return true;
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
        $accessControl = input('access_control', 1); // 权限控制 1.无权限 2.有权限

        if (!$suiteId || !$corpId) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $query = SuiteCorpGroupChat::find()
            ->when($accessControl, function ($query) {
                $query->andWhere([
                    'exists',
                    SuiteCorpGroupChatMember::find()
                        ->accessControl('suite_corp_group_chat_member.userid', 'userid')
                        ->andWhere('suite_corp_group_chat_member.group_chat_id = suite_corp_group_chat.id')
                ]);
            })
            ->andWhere(['suite_id' => $suiteId])
            ->andWhere(['corp_id' => $corpId]);

        // 客户群ID集合
        if ($chatId = self::getArray($params, 'chat_id')) {
            $query->andWhere(['in', 'chat_id', $chatId]);
        }

        // 群名称
        if ($name = self::getString($params, 'name')) {
            $query->andWhere(["name" => $name]);
        }

        // 群组类型 1.客户群(外部群) 2.内部群
        if ($groupType = self::getInt($params, 'group_type')) {
            $query->andWhere(["group_type" => $groupType]);
        }

        // 解散 1.是 2.否
        if ($isDismiss = self::getInt($params, 'is_dismiss')) {
            $query->andWhere(["is_dismiss" => $isDismiss]);
        }

        $query->select(['id', 'chat_id', 'create_time', 'name', 'notes', 'is_modify', 'group_type', 'is_dismiss', 'member_count']);
        $total  = $query->count();
        $offset = ($page - 1) * $per_page;
        $resp   = [];
        if ($total > 0) {
            $resp = $query->orderBy(['id' => SORT_DESC])->offset($offset)->asArray()->limit($per_page)->all();
        }
        return [
            'GroupChat'  => $resp,
            'pagination' => [
                'page'     => $page,
                'per_page' => $per_page,
                'total'    => intval($total)
            ]
        ];
    }

    /**
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     */
    public static function createInternalGroup($params)
    {
        if (empty($params['chatid'])) {
            throw new ErrException(Code::PARAMS_ERROR, '不是群组,无需处理');
        }
        $owner      = $params['creator'] ?? ''; //群主
        $memberList = empty($params['members']) ? [] : $params['members'];

        $newMemberCount = count($memberList);
        $transaction    = \Yii::$app->db->beginTransaction();
        $isUpdate       = $memberUpdate = true; // 是否更新，true 是 false 否
        try {
            $groupChat = SuiteCorpGroupChat::findOne(['suite_id' => $params['suite_id'], 'corp_id' => $params['corp_id'], 'chat_id' => $params['chatid']]);
            if (empty($groupChat)) {
                $isUpdate  = false;
                $groupChat = new SuiteCorpGroupChat();
            } else {
                $memberCount = $groupChat->member_count;
            }
            $groupChat->load([
                'group_type'   => SuiteCorpGroupChat::GROUP_TYPE_2,
                'suite_id'     => $params['suite_id'],
                'corp_id'      => $params['corp_id'],
                'chat_id'      => $params['chatid'],
                'create_time'  => $params['room_create_time'],
                'owner'        => $owner,
                'member_count' => $newMemberCount,
                'is_modify'    => empty($params['name']) ? SuiteCorpGroupChat::IS_MODIFY_1 : SuiteCorpGroupChat::IS_MODIFY_2
            ], '');
            // 校验参数
            if (!$groupChat->validate()) {
                throw new ErrException(Code::PARAMS_ERROR, $groupChat->getError());
            }
            if (!$groupChat->save()) {
                throw new ErrException(Code::CREATE_ERROR, $groupChat->getError());
            }
            $groupChatId = $groupChat->getPrimaryKey();

            if ($isUpdate === true) {
                // 如果是更新操作，并且组成员数量 等于 已有成员数量 则不进行组成员表的数据更新操作
                if (!empty($memberCount) and $memberCount == $newMemberCount) {
                    $memberUpdate = false;
                } else {
                    SuiteCorpGroupChatMemberService::deleteAll($groupChatId);
                }
            }

            if ($memberList and $memberUpdate === true) {
                foreach ($memberList as $item) {
                    $role = SuiteCorpGroupChatMember::GROUP_CHAT_MEMBER_ROLE_1;
                    if ($item['memberid'] == $owner) {
                        $role = SuiteCorpGroupChatMember::GROUP_CHAT_MEMBER_ROLE_3;
                    }
                    $createData = [
                        'group_chat_id' => $groupChatId,
                        'userid'        => $item['memberid'],
                        'join_time'     => $item['jointime'],
                        'type'          => $item['type'],
                        'role'          => $role
                    ];
                    $nickname   = Account::find()
                        ->andWhere(['userid' => $item['memberid']])
                        ->andWhere(['suite_id' => $params['suite_id']])
                        ->andWhere(['corp_id' => $params['corp_id']])
                        ->select('nickname')
                        ->scalar();
                    if ($nickname) {
                        $createData['name'] = $nickname;
                    }
                    SuiteCorpGroupChatMemberService::create($createData);
                }
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        return $groupChatId;
    }

    /**
     * @param $params
     * @return array
     * @throws ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public static function details($params)
    {
        if (!$id = self::getInt($params, 'id')) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $groupChat = SuiteCorpGroupChat::find()
            ->select(['id', 'chat_id', 'name', 'notes', 'is_modify', 'group_type', 'create_time', 'owner', 'notice', 'dismiss_time', 'member_count'])
            ->with('groupChatMemberById')
            ->andWhere(['id' => $id])
            ->asArray()
            ->one();
        if (!$groupChat) {
            throw new ErrException(Code::DATA_ERROR);
        }
        return $groupChat;
    }

    /**
     * @param $params
     * @return array|\yii\db\ActiveRecord[]
     * @throws ErrException
     */
    public static function detailsByChat($params)
    {
        if (!$suiteId = self::getString($params, 'suite_id')) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        if (!$corpId = self::getString($params, 'corp_id')) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        if (!$chatId = self::getArray($params, 'chat_id')) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        return SuiteCorpGroupChat::find()
            ->select(['id', 'chat_id', 'name', 'notes', 'group_type', 'create_time', 'owner', 'notice', 'dismiss_time', 'member_count'])
            ->andWhere(['suite_id' => $suiteId])
            ->andWhere(['corp_id' => $corpId])
            ->andWhere(['chat_id' => $chatId])
            ->asArray()
            ->all();
    }
}
