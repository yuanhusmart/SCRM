<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpGroupChat;
use common\models\SuiteCorpGroupChatMember;

/**
 * Class SuiteCorpGroupChatMemberService
 * @package common\services
 */
class SuiteCorpGroupChatMemberService extends Service
{

    const CHANGE_FIELDS = ['group_chat_id', 'userid', 'type', 'unionid', 'join_time', 'join_scene', 'invitor_userid', 'group_nickname', 'name', 'role', 'group_nickname_is_modify','chat_agree'];

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
        $attributes['group_nickname_is_modify'] = SuiteCorpGroupChatMember::GROUP_NICKNAME_IS_MODIFY_2;
        if (empty($attributes['name']) && empty($attributes['group_nickname'])) {
            $attributes['group_nickname_is_modify'] = SuiteCorpGroupChatMember::GROUP_NICKNAME_IS_MODIFY_1;
        }
        $create = new SuiteCorpGroupChatMember();
        $create->load($attributes, '');
        //校验参数
        if (!$create->validate()) {
            throw new ErrException(Code::PARAMS_ERROR, $create->getError());
        }
        if (!$create->save()) {
            throw new ErrException(Code::CREATE_ERROR, $create->getError());
        }
        return $create->getPrimaryKey();
    }

    /**
     * @param $params
     * @return true
     * @throws ErrException
     */
    public static function update($params)
    {
        $id = self::getId($params);
        if (!$id) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data = SuiteCorpGroupChatMember::findOne($id);
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
        $data = SuiteCorpGroupChatMember::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        if (!$data->delete()) {
            throw new ErrException(Code::DATA_ERROR);
        }
        return true;
    }

    /**
     * @param $groupChatId
     * @return int
     */
    public static function deleteAll($groupChatId)
    {
        return SuiteCorpGroupChatMember::deleteAll(['group_chat_id' => $groupChatId]);
    }

    /**
     * 获取企业员工群组数量根据用户ID
     * @param $params
     * @return bool|int|string|null
     * @throws ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public static function getUserGroupCountById($params)
    {
        $userid = self::getString($params, 'userid');
        if (empty($userid)) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        return SuiteCorpGroupChatMember::find()
                                       ->andWhere(['userid' => $userid])
                                       ->andWhere(['type' => SuiteCorpGroupChatMember::GROUP_CHAT_TYPE_1])
                                       ->count();
    }

    /**
     * @param $groupChatId
     * @param $items
     * @return int
     * @throws \yii\db\Exception
     */
    public static function batchInsertGroupChatMember($groupChatId, $items)
    {
        $insertData = [];
        foreach ($items as $item) {
            $groupNicknameIsModify = SuiteCorpGroupChatMember::GROUP_NICKNAME_IS_MODIFY_2;
            if (empty($item['name']) && empty($item['group_nickname'])) {
                $groupNicknameIsModify = SuiteCorpGroupChatMember::GROUP_NICKNAME_IS_MODIFY_1;
            }
            $insertData[] = [
                $groupChatId,
                $item['userid'],
                $item['type'],
                $item['unionid'] ?? '',
                $item['join_time'] ?? 0,
                $item['join_scene'] ?? SuiteCorpGroupChatMember::JOIN_SCENE_1,
                $item['invitor_userid'] ?? '',
                $item['group_nickname'] ?? '',
                $item['name'] ?? '',
                $item['role'] ?? SuiteCorpGroupChatMember::GROUP_CHAT_MEMBER_ROLE_1,
                $groupNicknameIsModify
            ];
        }
        return \Yii::$app->db->createCommand()->batchInsert(SuiteCorpGroupChatMember::tableName(), self::CHANGE_FIELDS, $insertData)->execute();
    }

    /**
     * 查询群组的群主信息
     * @param $params
     * @return array|\yii\db\ActiveRecord[]
     * @throws ErrException
     */
    public static function getOwner($params)
    {
        $suiteId = self::getString($params, 'suite_id');
        $corpId  = self::getString($params, 'corp_id');
        $chatId  = self::getArray($params, 'chat_id');
        if (!$suiteId || !$corpId || !$chatId) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data = SuiteCorpGroupChatMember::find()
                                        ->alias('a')
                                        ->leftJoin(SuiteCorpGroupChat::tableName() . ' AS b', 'a.group_chat_id = b.id')
                                        ->select('b.chat_id,a.userid,a.name,a.type')
                                        ->andWhere(['b.suite_id' => $suiteId])
                                        ->andWhere(['b.corp_id' => $corpId])
                                        ->andWhere(['IN', 'b.chat_id', $chatId])
                                        ->andWhere(['<>', 'b.owner', ''])
                                        ->andWhere(['a.role' => SuiteCorpGroupChatMember::GROUP_CHAT_MEMBER_ROLE_3])
                                        ->asArray()
                                        ->all();
        return $data;
    }

}
