<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpSessionsMember;

/**
 * Class SuiteCorpSessionsMemberService
 * @package common\services
 */
class SuiteCorpSessionsMemberService extends Service
{

    /**
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     */
    public static function create($params)
    {
        $attributes = self::includeKeys($params, SuiteCorpSessionsMember::CHANGE_FIELDS);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $create = SuiteCorpSessionsMember::findOne(['suite_id' => $attributes['suite_id'], 'corp_id' => $attributes['corp_id'], 'session_id' => $attributes['session_id'], 'userid' => $attributes['userid']]);
        if ($create) {
            throw new ErrException(Code::PARAMS_ERROR, '已存在，无需重复添加');
        }
        $create = new SuiteCorpSessionsMember();

        $create->load($attributes, '');
        //校验参数
        if (!$create->validate()) {
            throw new ErrException(Code::PARAMS_ERROR, $create->getErrors());
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
        $data = SuiteCorpSessionsMember::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        $attributes = self::includeKeys($params, SuiteCorpSessionsMember::CHANGE_FIELDS);
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
        $data = SuiteCorpSessionsMember::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        if (!$data->delete()) {
            throw new ErrException(Code::DATA_ERROR);
        }
        return true;
    }

}
