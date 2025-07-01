<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\Account;
use common\models\SuiteCorpConfig;
use common\models\SuiteCorpLicenseActiveInfo;
use common\models\SuiteCorpTokenRecord;
use common\models\SuitePackage;

/**
 * Class SuiteCorpConfigService
 * @package common\services
 */
class SuiteCorpConfigService extends Service
{

    /**
     * @param $params
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public static function items($params)
    {
        list($page, $per_page) = self::getPageInfo($params);
        $query = SuiteCorpConfig::find();

        // 企业名称或ID
        if ($keyword = self::getString($params, 'keyword')) {
            $query->andWhere([
                "OR",
                ['=', 'corp_id', $keyword],
                ['like', 'corp_name', $keyword]
            ]);
        }

        // 企业购买的套餐ID,关联服务商套餐表主键ID
        if ($packageId = self::getInt($params, 'package_id')) {
            $query->andWhere(["package_id" => $packageId]);
        }

        // 状态：1.启用 2.禁用
        if ($status = self::getInt($params, 'status')) {
            $query->andWhere(["status" => $status]);
        }

        $total  = $query->count();
        $offset = ($page - 1) * $per_page;
        $resp   = [];
        $query->select(['id', 'suite_id', 'corp_id', 'corp_name', 'status', 'created_at', 'updated_at', 'package_id', 'corp_scale', 'corp_industry', 'corp_sub_industry', 'subject_type', 'corp_type', 'verified_end_time', 'tokens', 'use_tokens']);
        if ($total > 0) {
            $resp = $query->with(['licenseActiveInfoCount', 'accountCount', 'packageById', 'configChatAuthCount'])
                          ->orderBy(['id' => SORT_DESC])
                          ->offset($offset)
                          ->limit($per_page)
                          ->asArray()
                          ->all();
        }
        return [
            'CorpConfig' => $resp,
            'pagination' => [
                'page'     => $page,
                'per_page' => $per_page,
                'total'    => intval($total)
            ]
        ];
    }

    /**
     * @param $params
     * @return array|\yii\db\ActiveRecord
     * @throws ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public static function details($params)
    {
        if (!$id = self::getId($params)) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $corpConfig = SuiteCorpConfig::find()
                                     ->with(['licenseActiveInfoCount', 'accountCount', 'packageById', 'configChatAuthCount'])
                                     ->select(['id', 'suite_id', 'corp_id', 'corp_name', 'status', 'created_at', 'updated_at', 'package_id', 'corp_scale', 'corp_industry', 'corp_sub_industry', 'subject_type', 'corp_type', 'verified_end_time', 'tokens', 'use_tokens'])
                                     ->andWhere(['id' => $id])
                                     ->asArray()
                                     ->one();
        if (!$corpConfig) {
            throw new ErrException(Code::DATA_ERROR);
        }
        return $corpConfig;
    }

    /**
     * @param $params
     * @return true
     * @throws ErrException
     */
    public static function updateStatus($params)
    {
        $id     = self::getId($params);
        $status = self::getInt($params, 'status');
        if (!$id || !$status || !in_array($status, [SuiteCorpConfig::STATUS_1, SuiteCorpConfig::STATUS_2])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data = SuiteCorpConfig::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        if ($data->status == $status) {
            throw new ErrException(Code::NOT_EXIST, '当前：' . SuiteCorpConfig::STATUS_DESC[$status] . ',状态一致无需改变');
        }
        $data->status = $status;
        if (!$data->save()) {
            throw new ErrException(Code::UPDATE_ERROR, $data->getError());
        }
        return true;
    }

    /**
     * @param $params
     * @return true
     * @throws ErrException
     */
    public static function updateIsAutoAuth($params)
    {
        $id         = self::getId($params);
        $isAutoAuth = self::getInt($params, 'is_auto_auth');
        if (!$id || !$isAutoAuth || !in_array($isAutoAuth, array_keys(SuiteCorpConfig::IS_AUTO_AUTH_DESC))) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data = SuiteCorpConfig::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        if ($data->is_auto_auth == $isAutoAuth) {
            throw new ErrException(Code::NOT_EXIST, '当前：' . SuiteCorpConfig::IS_AUTO_AUTH_DESC[$isAutoAuth] . ',自动授权状态一致无需改变');
        }
        $data->is_auto_auth = $isAutoAuth;
        if (!$data->save()) {
            throw new ErrException(Code::UPDATE_ERROR, $data->getError());
        }
        return true;
    }

    /**
     * @param $params
     * @return true
     * @throws ErrException
     * @throws \yii\db\Exception
     */
    public static function createOrUpdate($params)
    {
        $attributes = self::includeKeys($params, SuiteCorpConfig::CHANGE_FIELDS);
        if (empty($params['suite_id']) || empty($params['corp_id'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $data = SuiteCorpConfig::findOne(['suite_id' => $params['suite_id'], 'corp_id' => $params['corp_id']]);
            if (empty($data)) {
                $data                     = new SuiteCorpConfig();
                $attributes['suite_id']   = $params['suite_id'];
                $attributes['corp_id']    = $params['corp_id'];
                $attributes['created_at'] = time();
            }

            // 默认绑定试用版套餐
            $package = SuitePackage::find()->where(['type' => 3, 'status' => 1])->asArray()->one();
            if (!empty($package)) {
                $attributes['package_id'] = $package['id'];
                $data->initRole();
            }

            $data->attributes = $attributes;
            if (!$data->save()) {
                throw new ErrException(Code::UPDATE_ERROR, $data->getErrors());
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        return true;
    }

    /**
     * @param $params
     * @return true
     * @throws ErrException
     * @throws \yii\db\Exception
     */
    public static function updateTokens($params)
    {
        $id     = self::getId($params);
        $tokens = self::getInt($params, 'tokens');
        if (!$id || !$tokens) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $data = SuiteCorpConfig::findOne($id);
            if (!$data) {
                throw new ErrException(Code::NOT_EXIST);
            }
            if ($data->use_tokens >= $tokens) {
                throw new ErrException(Code::NOT_EXIST, 'token总数需要大于已使用token数量');
            }

            if ($data->tokens == $tokens) {
                throw new ErrException(Code::NOT_EXIST, 'token无变化');
            }

            // 当前token数 = 已购总数 - 使用总数
            $currentTokens = $data->tokens - $data->use_tokens;

            $surplusTokens = ($tokens - $data->tokens) + $currentTokens;

            $data->tokens = $tokens;
            if (!$data->save()) {
                throw new ErrException(Code::UPDATE_ERROR, $data->getError());
            }

            // 增加token使用记录
            $tokenRecord                 = new SuiteCorpTokenRecord();
            $tokenRecord->batch_id       = '';
            $tokenRecord->suite_id       = $data->suite_id;
            $tokenRecord->corp_id        = $data->corp_id;
            $tokenRecord->input_tokens   = 0;
            $tokenRecord->output_tokens  = 0;
            $tokenRecord->current_tokens = $currentTokens;
            $tokenRecord->surplus_tokens = $surplusTokens;
            $tokenRecord->analysis_type  = SuiteCorpTokenRecord::ANALYSIS_TYPE_4;
            $tokenRecord->analysis_date  = date('Y-m-d', time());
            $tokenRecord->analysis_time  = time();
            $tokenRecord->updated_at     = time();
            if (!$tokenRecord->save()) {
                throw new ErrException(Code::CREATE_ERROR, $tokenRecord->getErrors());
            }

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        return true;
    }

}