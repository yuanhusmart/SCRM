<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\Account;
use common\models\SuiteCorpConfig;
use common\models\SuiteCorpConfigAdminList;

/**
 * Class SuiteCorpConfigAdminListService
 * @package common\services
 */
class SuiteCorpConfigAdminListService extends Service
{

    /**
     * @param $params
     * @return true
     * @throws ErrException
     * @throws \yii\db\Exception
     */
    public static function eventChangeAuth($params)
    {
        if (empty($params['AuthCorpId'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        $query = SuiteCorpConfig::find()->andWhere(['corp_id' => $params['AuthCorpId']]);
        if (!empty($params['SuiteId'])) {
            $query->andWhere(['suite_id' => $params['SuiteId']]);
        }

        $config = $query->one();
        if (empty($config)) {
            throw new ErrException(Code::PARAMS_ERROR, '未找到企业及相关应用');
        }
        $adminList = SuiteService::agentGetAdminList($config->suite_id, $config->corp_id);
        if (!empty($adminList['admin'])) {
            $transaction = \Yii::$app->db->beginTransaction();
            try {
                self::deleteAll($config->id);
                self::batchInsertConfigAdminList($config->id, $adminList['admin']);
                $transaction->commit();
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            }
        }
        return true;
    }

    /**
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     */
    public static function create($params)
    {
        $attributes = self::includeKeys($params, SuiteCorpConfigAdminList::CHANGE_FIELDS);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        if (empty($params['config_id']) || empty($params['userid'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $create = SuiteCorpConfigAdminList::find()->where(['config_id' => $params['config_id'], 'userid' => $params['userid']])->one();
        if (empty($create)) {
            $create = new SuiteCorpConfigAdminList();
        }
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
        $data = SuiteCorpConfigAdminList::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        $attributes = self::includeKeys($params, SuiteCorpConfigAdminList::CHANGE_FIELDS);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data->attributes = $attributes;
        if (!$data->save()) {
            throw new ErrException(Code::UPDATE_ERROR, $data->getErrors());
        }
        return true;
    }

    /**
     * @param $configId
     * @return int
     */
    public static function deleteAll($configId)
    {
        return SuiteCorpConfigAdminList::deleteAll(['config_id' => $configId]);
    }

    /**
     * @param $configId
     * @param $items
     * @return int
     * @throws \yii\db\Exception
     */
    public static function batchInsertConfigAdminList($configId, $items)
    {
        $insertData = [];
        foreach ($items as $item) {
            $insertData[] = [$configId, $item['userid'], $item['auth_type']];
        }
        return \Yii::$app->db->createCommand()->batchInsert(SuiteCorpConfigAdminList::tableName(), SuiteCorpConfigAdminList::CHANGE_FIELDS, $insertData)->execute();
    }

    /**
     * @param $params
     * @return array
     * @throws ErrException
     */
    public static function items($params)
    {
        list($page, $per_page) = self::getPageInfo($params);
        $configId = self::getInt($params, 'config_id');
        if (!$configId) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $query = SuiteCorpConfigAdminList::find()
                                         ->select([
                                             SuiteCorpConfigAdminList::tableName() . '.*',
                                             Account::tableName() . '.nickname',
                                             Account::tableName() . '.jnumber',
                                             Account::tableName() . '.avatar',
                                         ])
                                         ->leftJoin(SuiteCorpConfig::tableName(), SuiteCorpConfig::tableName() . '.id = ' . SuiteCorpConfigAdminList::tableName() . '.config_id')
                                         ->leftJoin(Account::tableName(), Account::tableName() . '.suite_id = ' . SuiteCorpConfig::tableName() . '.suite_id and ' .
                                                                          Account::tableName() . '.corp_id = ' . SuiteCorpConfig::tableName() . '.corp_id and ' .
                                                                          Account::tableName() . '.userid = ' . SuiteCorpConfigAdminList::tableName() . '.userid')
                                         ->andWhere([SuiteCorpConfigAdminList::tableName() . ".config_id" => $configId]);

        if ($userid = self::getString($params, 'userid')) {
            $query->andWhere([SuiteCorpConfigAdminList::tableName() . ".userid" => $userid]);
        }

        $total  = $query->count();
        $offset = ($page - 1) * $per_page;
        $resp   = [];
        if ($total > 0) {
            $resp = $query->orderBy([SuiteCorpConfigAdminList::tableName() . '.id' => SORT_DESC])->offset($offset)->limit($per_page)->asArray()->all();
        }
        return [
            'ConfigAdminList' => $resp,
            'pagination'      => [
                'page'     => $page,
                'per_page' => $per_page,
                'total'    => intval($total)
            ]
        ];
    }

}
