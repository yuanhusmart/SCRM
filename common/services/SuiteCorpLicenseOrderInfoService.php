<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpLicenseOrderInfo;

/**
 * Class SuiteCorpLicenseOrderInfoService
 * @package common\services
 */
class SuiteCorpLicenseOrderInfoService extends Service
{

    // 可修改字段
    const CHANGE_FIELDS = ['license_order_id', 'corp_id', 'sub_order_id', 'account_base_count', 'account_external_contact_count', 'account_duration_months', 'account_duration_days', 'auto_active_status'];

    /**
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     */
    public static function create($params)
    {
        $accountBaseCount = $accountExternalContactCount = $accountDurationMonths = $accountDurationDays = 0;
        if (!empty($params['account_count'])) {
            $accountBaseCount            = $params['account_count']['base_count'] ?? 0;
            $accountExternalContactCount = $params['account_count']['external_contact_count'] ?? 0;
        }

        if (!empty($params['account_duration'])) {
            $accountDurationMonths = $params['account_duration']['months'] ?? 0;
            $accountDurationDays   = $params['account_duration']['days'] ?? 0;
        }
        $params['account_base_count']             = $accountBaseCount;
        $params['account_external_contact_count'] = $accountExternalContactCount;
        $params['account_duration_months']        = $accountDurationMonths;
        $params['account_duration_days']          = $accountDurationDays;
        if (!empty($params['corpid'])) {
            $params['corp_id'] = $params['corpid'];
        }
        $attributes = self::includeKeys($params, self::CHANGE_FIELDS);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        $where = ['license_order_id' => $attributes['license_order_id'], 'corp_id' => $attributes['corp_id']];
        if (!empty($attributes['sub_order_id'])) {
            $where['sub_order_id'] = $attributes['sub_order_id'];
        }
        $licenseOrder = SuiteCorpLicenseOrderInfo::findOne($where);
        // 如果数据不存在 写入主表 + 媒体数据
        if (empty($licenseOrder)) {
            $licenseOrder = new SuiteCorpLicenseOrderInfo();
            $licenseOrder->load($attributes, '');
        } else {
            $licenseOrder->attributes = $attributes;
        }
        //校验参数
        if (!$licenseOrder->validate()) {
            throw new ErrException(Code::PARAMS_ERROR, $licenseOrder->getErrors());
        }
        if (!$licenseOrder->save()) {
            throw new ErrException(Code::CREATE_ERROR, $licenseOrder->getErrors());
        }
        return $licenseOrder->getPrimaryKey();
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
        $data = SuiteCorpLicenseOrderInfo::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        $attributes = self::includeKeys($params, self::CHANGE_FIELDS);
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
        $data = SuiteCorpLicenseOrderInfo::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        if (!$data->delete()) {
            throw new ErrException(Code::DATA_ERROR);
        }
        return true;
    }

    /**
     * @param $corpMomentId
     * @return int
     */
    public static function deleteAll($licenseOrderId)
    {
        return SuiteCorpLicenseOrderInfo::deleteAll(['license_order_id' => $licenseOrderId]);
    }

}
