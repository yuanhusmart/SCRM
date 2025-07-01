<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpExternalContact;
use common\models\SuiteCorpExternalContactFollowUser;
use common\models\SuiteCorpGroupChat;
use common\models\SuiteCorpGroupChatMember;
use common\models\SuiteCorpNameHistory;

/**
 * Class SuiteCorpNameHistoryService
 * @package common\services
 */
class SuiteCorpNameHistoryService extends Service
{

    /**
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     */
    public static function create($params)
    {
        if (empty($params['suite_id']) || empty($params['corp_id'])) {
            throw new ErrException(Code::PARAMS_ERROR, '缺少企业信息');
        }

        $type = self::getInt($params, 'type');
        if (!in_array($type, array_keys(SuiteCorpNameHistory::GROUP_NAME_HISTORY_TYPE))) {
            throw new ErrException(Code::PARAMS_ERROR, '请确认类型');
        }

        if (empty($params['origin_userid']) || empty($params['origin_name'])) {
            throw new ErrException(Code::PARAMS_ERROR, '缺少源头用户信息');
        }

        if (empty($params['name'])) {
            throw new ErrException(Code::PARAMS_ERROR, '缺少名称信息');
        }

        if (empty($params['creator']) || empty($params['create_number']) || empty($params['create_userid'])) {
            throw new ErrException(Code::PARAMS_ERROR, '缺少操作人信息');
        }

        $attributes = self::includeKeys($params, SuiteCorpNameHistory::CHANGE_FIELDS);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        $transaction = \Yii::$app->db->beginTransaction();
        try {

            // 类型：1.群组 2.群组成员id 3.外部联系人  * 优先处理原数据
            switch ($type) {
                case SuiteCorpNameHistory::GROUP_NAME_HISTORY_TYPE_1:
                    if (empty($params['chat_id'])) {
                        throw new ErrException(Code::PARAMS_ERROR, '缺少群组信息');
                    }

                    $groupChatId = SuiteCorpGroupChat::find()
                                                     ->select('id')
                                                     ->andWhere(['suite_id' => $params['suite_id'], 'corp_id' => $params['corp_id'], 'chat_id' => $params['chat_id']])
                                                     ->limit(1)
                                                     ->scalar();

                    if (empty($groupChatId)) {
                        throw new ErrException(Code::PARAMS_ERROR, '群组信息未找到');
                    }

                    SuiteCorpGroupChat::updateAll(['name' => $params['name']], ['id' => $groupChatId]);
                break;

                case SuiteCorpNameHistory::GROUP_NAME_HISTORY_TYPE_2:
                    if (empty($params['chat_id']) || empty($params['userid'])) {
                        throw new ErrException(Code::PARAMS_ERROR, '缺少群组或群组成员信息');
                    }

                    $groupChatMemberId = SuiteCorpGroupChatMember::find()
                                                                 ->andWhere(['Exists',
                                                                     SuiteCorpGroupChat::find()
                                                                                       ->andWhere(SuiteCorpGroupChat::tableName() . ".id=" . SuiteCorpGroupChatMember::tableName() . ".group_chat_id")
                                                                                       ->andWhere([SuiteCorpGroupChat::tableName() . '.suite_id' => $params['suite_id']])
                                                                                       ->andWhere([SuiteCorpGroupChat::tableName() . '.corp_id' => $params['corp_id']])
                                                                                       ->andWhere([SuiteCorpGroupChat::tableName() . '.chat_id' => $params['chat_id']])
                                                                 ])
                                                                 ->andWhere(['userid' => $params['userid']])
                                                                 ->select('id')
                                                                 ->limit(1)
                                                                 ->scalar();

                    if (empty($groupChatMemberId)) {
                        throw new ErrException(Code::PARAMS_ERROR, '群组用户信息未找到');
                    }

                    SuiteCorpGroupChatMember::updateAll(['group_nickname' => $params['name']], ['id' => $groupChatMemberId]);
                break;

                case SuiteCorpNameHistory::GROUP_NAME_HISTORY_TYPE_3:
                    if (empty($params['userid'])) {
                        throw new ErrException(Code::PARAMS_ERROR, '缺少联系人信息');
                    }

                    $externalContactId = SuiteCorpExternalContact::find()
                                                                 ->select('id')
                                                                 ->andWhere(['suite_id' => $params['suite_id'], 'corp_id' => $params['corp_id'], 'external_userid' => $params['userid']])
                                                                 ->limit(1)
                                                                 ->scalar();

                    if (empty($externalContactId)) {
                        // 外部联系人信息未找到 进行自动创建
                        $externalContact = new SuiteCorpExternalContact();
                        $externalContact->load(['suite_id' => $params['suite_id'], 'corp_id' => $params['corp_id'], 'name' => $params['name'], 'is_modify' => SuiteCorpExternalContact::IS_MODIFY_1, 'external_userid' => $params['userid']], '');
                        // 校验参数
                        if (!$externalContact->validate()) {
                            throw new ErrException(Code::PARAMS_ERROR, $externalContact->getError());
                        }
                        if (!$externalContact->save()) {
                            throw new ErrException(Code::CREATE_ERROR, $externalContact->getError());
                        }
                        $externalContactId = $externalContact->getPrimaryKey();
                        $exists            = SuiteCorpExternalContactFollowUser::find()->where(['external_contact_id' => $externalContactId, 'userid' => $params['origin_userid']])->exists();
                        if (!$exists) {
                            $externalContactFollowUserId = SuiteCorpExternalContactFollowUserService::create(['external_contact_id' => $externalContactId, 'userid' => $params['origin_userid'], 'remark' => '【系统自动补充】', 'createtime' => time()]);
                            \Yii::warning('外部联系人3主键ID：' . $externalContactId . ',附表主键ID：' . $externalContactFollowUserId);
                        }
                    } else {
                        SuiteCorpExternalContact::updateAll(['name' => $params['name']], ['id' => $externalContactId]);
                    }
                break;
            }

            $create = new SuiteCorpNameHistory();
            $create->load($attributes, '');
            //校验参数
            if (!$create->validate()) {
                throw new ErrException(Code::PARAMS_ERROR, $create->getErrors());
            }
            if (!$create->save()) {
                throw new ErrException(Code::CREATE_ERROR, $create->getErrors());
            }
            $id = $create->getPrimaryKey();
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            \Yii::warning($e);
            throw new ErrException(Code::DATA_ERROR, $e->getMessage());
        }
        return $id;
    }

    /**
     * @param $params
     * @return array
     * @throws ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public static function items($params)
    {
        list($page, $per_page) = self::getPageInfo($params);

        if (empty($params['suite_id']) || empty($params['corp_id'])) {
            throw new ErrException(Code::PARAMS_ERROR, '缺少企业信息');
        }

        $query = SuiteCorpNameHistory::find()
                                     ->andWhere(['suite_id' => $params['suite_id']])
                                     ->andWhere(['corp_id' => $params['corp_id']]);

        if ($type = self::getInt($params, 'type')) {
            $query->andWhere(['type' => $type]);
        }

        if ($originUserid = self::getString($params, 'origin_userid')) {
            $query->andWhere(['origin_userid' => $originUserid]);
        }

        if ($chatId = self::getString($params, 'chat_id')) {
            $query->andWhere(['chat_id' => $chatId]);
        }

        if ($userid = self::getString($params, 'userid')) {
            $query->andWhere(['userid' => $userid]);
        }

        $total  = $query->count();
        $offset = ($page - 1) * $per_page;
        $resp   = [];
        if ($total > 0) {
            $resp = $query->orderBy(['id' => SORT_DESC])->offset($offset)->limit($per_page)->asArray()->all();
        }
        return [
            'NameHistory' => $resp,
            'pagination'  => [
                'page'     => $page,
                'per_page' => $per_page,
                'total'    => intval($total)
            ]
        ];
    }

}
