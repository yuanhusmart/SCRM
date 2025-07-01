<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpHistoryAuthUserList;

/**
 * Class SuiteCorpHistoryAuthUserListService
 * @package common\services
 */
class SuiteCorpHistoryAuthUserListService extends Service
{

    /**
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     */
    public static function create($params)
    {
        $attributes = self::includeKeys($params, SuiteCorpHistoryAuthUserList::CHANGE_FIELDS);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $create = new SuiteCorpHistoryAuthUserList();
        $create->load($attributes, '');
        //校验参数
        if (!$create->validate()) {
            throw new ErrException(Code::PARAMS_ERROR, $create->getErrors());
        }
        if (!$create->save()) {
            throw new ErrException(Code::CREATE_ERROR, $create->getErrors());
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
        $data = SuiteCorpHistoryAuthUserList::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        $attributes = self::includeKeys($params, SuiteCorpHistoryAuthUserList::CHANGE_FIELDS);
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
     * @return array|\yii\db\ActiveRecord[]
     * @throws ErrException
     */
    public static function items($params)
    {
        $suiteId = self::getString($params, 'suite_id');
        $corpId  = self::getString($params, 'corp_id');
        $userid  = self::getString($params, 'userid');
        if (!$suiteId || !$corpId || !$userid) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        return SuiteCorpHistoryAuthUserList::find()
                                           ->select(['edition_list', 'start_time', 'end_time'])
                                           ->where(['suite_id' => $suiteId, 'corp_id' => $corpId, 'userid' => $userid])
                                           ->orderBy(['id' => SORT_ASC])
                                           ->asArray()
                                           ->all();
    }

}
