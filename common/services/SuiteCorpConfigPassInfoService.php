<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpConfigPassInfo;

/**
 * Class SuiteCorpConfigPassInfoService
 * @package common\services
 */
class SuiteCorpConfigPassInfoService extends Service
{

    /**
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     */
    public static function create($params)
    {
        if (empty($params['config_id']) || empty($params['create_userid'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $params['ak'] = self::getRandomStr('24');
        $params['sk'] = self::getRandomStr('30');
        $attributes   = self::includeKeys($params, SuiteCorpConfigPassInfo::CHANGE_FIELDS);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $create = new SuiteCorpConfigPassInfo();
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
    public static function updateStatus($params)
    {
        $id           = self::getId($params);
        $status       = self::getInt($params, 'status');
        $updateUserid = self::getString($params, 'update_userid');
        if (!$id || !$status || !$updateUserid || !in_array($status, [SuiteCorpConfigPassInfo::STATUS_1, SuiteCorpConfigPassInfo::STATUS_2])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data = SuiteCorpConfigPassInfo::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        if ($data->status == $status) {
            throw new ErrException(Code::NOT_EXIST, '当前：' . SuiteCorpConfigPassInfo::STATUS_DESC[$status] . ',状态一致无需改变');
        }
        $data->status        = $status;
        $data->update_userid = $updateUserid;
        if (!$data->save()) {
            throw new ErrException(Code::UPDATE_ERROR, $data->getErrors());
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
        $configId = self::getInt($params, 'config_id');
        if (!$configId) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $query = SuiteCorpConfigPassInfo::find()->andWhere(["config_id" => $configId])->andWhere(["data_status" => SuiteCorpConfigPassInfo::DATA_STATUS_1]);
        if ($id = self::getId($params)) {
            $query->andWhere(["id" => $id]);
        }
        if ($status = self::getInt($params, 'status')) {
            $query->andWhere(["status" => $status]);
        }
        $field = ['id','config_id', 'created_at', 'updated_at', 'ak', 'status'];
        // 返回加密 1 加密 2 非加密 （默认1）
        $isEncryption = self::getInt($params, 'is_encryption') ?? 1;
        if ($isEncryption == 1) {
            $field[] = "('***************') AS sk";
        } else {
            $field[] = 'sk';
        }
        $query->select($field);

        $total  = $query->count();
        $offset = ($page - 1) * $per_page;
        $resp   = [];
        if ($total > 0) {
            $resp = $query->orderBy(['id' => SORT_DESC])->offset($offset)->limit($per_page)->asArray()->all();
        }
        return [
            'ConfigPassInfo' => $resp,
            'pagination'     => [
                'page'     => $page,
                'per_page' => $per_page,
                'total'    => intval($total)
            ]
        ];
    }

    /**
     * @param $params
     * @return true
     * @throws ErrException
     */
    public static function delete($params)
    {
        $id           = self::getId($params);
        $updateUserid = self::getString($params, 'update_userid');
        if (!$id || !$updateUserid) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data = SuiteCorpConfigPassInfo::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        $data->update_userid = $updateUserid;
        $data->data_status   = SuiteCorpConfigPassInfo::DATA_STATUS_2;
        if (!$data->save()) {
            throw new ErrException(Code::UPDATE_ERROR, $data->getErrors());
        }
        return true;
    }

}
