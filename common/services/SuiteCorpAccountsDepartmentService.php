<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpAccountsDepartment;

/**
 * Class SuiteCorpAccountsDepartmentService
 * @package common\services
 */
class SuiteCorpAccountsDepartmentService extends Service
{

    /**
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     */
    public static function create($params)
    {
        $attributes = self::includeKeys($params, SuiteCorpAccountsDepartment::CHANGE_FIELDS);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $create = new SuiteCorpAccountsDepartment();
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
    public static function batchInsertAccountsDepartment($suiteId, $corpId, $userid, $departments, $isLeaderInDept)
    {
        $insertData = [];
        foreach ($departments as $key => $item) {
            $insertData[] = [$suiteId, $corpId, $item, $userid, $isLeaderInDept[$key] ?? SuiteCorpAccountsDepartment::IS_LEADER_IN_DEPT_0];
        }
        return \Yii::$app->db->createCommand()->batchInsert(
            SuiteCorpAccountsDepartment::tableName(),
            SuiteCorpAccountsDepartment::CHANGE_FIELDS,
            $insertData
        )->execute();
    }

    /**
     * @param $suiteId
     * @param $corpId
     * @param $userid
     * @return int
     */
    public static function deleteAllByUserid($suiteId, $corpId, $userid)
    {
        return SuiteCorpAccountsDepartment::deleteAll(['suite_id' => $suiteId, 'corp_id' => $corpId, 'userid' => $userid]);
    }

}
