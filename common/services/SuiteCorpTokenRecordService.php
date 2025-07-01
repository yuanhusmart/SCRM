<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpConfig;
use common\models\SuiteCorpTokenRecord;
use common\sdk\TableStoreChain;

/**
 * Class SuiteCorpTokenRecordService
 * @package common\services
 */
class SuiteCorpTokenRecordService extends Service
{
    /**
     * 验证参数
     * @param $params
     * @return array
     * @throws ErrException
     */
    public static function verifyParams($params): array
    {
        $verifyParam = self::includeKeys($params, ['suite_id', 'corp_id', 'current_tokens', 'surplus_tokens', 'input_tokens', 'output_tokens', 'analysis_type', 'analysis_date', 'analysis_time']);

        if (empty($verifyParam['suite_id'])) {
            throw new ErrException(Code::PARAMS_ERROR, '请输入服务商ID');
        }

        if (empty($verifyParam['corp_id'])) {
            throw new ErrException(Code::PARAMS_ERROR, '请输入企业ID');
        }

        if (empty($verifyParam['wx_id']) || empty($verifyParam['staff']) || empty($verifyParam['customer']) || empty($verifyParam['session_id'])) {
            throw new ErrException(Code::PARAMS_ERROR, '请输入被分析对象');
        }

        if (!isset($verifyParam['analysis_type']) || !in_array($verifyParam['analysis_type'], array_keys(SuiteCorpTokenRecord::ANALYSIS_TYPE_DESC))) {
            throw new ErrException(Code::PARAMS_ERROR, '请确认分析类型');
        }

        $verifyParam['session_time_start'] = empty($verifyParam['session_time_start']) ? 0 : $verifyParam['session_time_start'];

        $verifyParam['session_time_end'] = empty($verifyParam['session_time_end']) ? time() : $verifyParam['session_time_end'];

        $verifyParam['result'] = empty($verifyParam['result']) ? '' : $verifyParam['result'];

        $verifyParam['analysis_time'] = empty($verifyParam['analysis_time']) ? time() : $verifyParam['analysis_time'];

        $verifyParam['analysis_date'] = date('Y-m-d', $verifyParam['analysis_time']);

        return $verifyParam;
    }

    /**
     * 更新Token记录
     * @param $params
     * @return int
     * @throws ErrException
     */
    public static function update($params)
    {
        $attributes = self::verifyParams($params);
        $id         = self::getId($params);

        if (!$id) {
            throw new ErrException(Code::PARAMS_ERROR, '请提供记录ID');
        }

        $data = SuiteCorpTokenRecord::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST, '记录不存在');
        }

        $data->attributes = $attributes;
        if (!$data->save()) {
            throw new ErrException(Code::UPDATE_ERROR, $data->getErrors());
        }

        return $id;
    }

    /**
     * 查询Token记录列表
     * @param $params
     * @return array
     * @throws ErrException
     */
    public static function corpItems($params)
    {
        list($page, $per_page) = self::getPageInfo($params);
        $query = SuiteCorpTokenRecord::find();

        // 服务商ID
        if (!$suiteId = self::getString($params, 'suite_id')) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        // 企业ID
        if (!$corpId = self::getString($params, 'corp_id')) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $query->andWhere(['suite_id' => $suiteId])->andWhere(['corp_id' => $corpId]);

        // 分析类型
        if ($analysisType = self::getInt($params, 'analysis_type')) {
            $query->andWhere(['analysis_type' => $analysisType]);
        }

        // 分析日期范围
        if ($startDate = self::getString($params, 'start_date')) {
            $query->andWhere(['>=', 'analysis_date', $startDate]);
        }

        if ($endDate = self::getString($params, 'end_date')) {
            $query->andWhere(['<=', 'analysis_date', $endDate]);
        }

        // 会话ID
        if ($sessionId = self::getString($params, 'analysis_session_id')) {
            $query->andWhere(['analysis_session_id' => $sessionId]);
        }

        $query->select(
            [
                'sum(input_tokens) + sum(output_tokens) as current_total_tokens',                    // 当日用量
                'count(id) as current_count',                                                        // 分析次数
                'min(surplus_tokens) as current_surplus_tokens',                                     // 分析次数
                'analysis_date'
            ]
        )->groupBy(['analysis_date']);

        $total  = $query->count();
        $offset = ($page - 1) * $per_page;
        $resp   = [];

        if ($total > 0) {
            $resp = $query->orderBy(['id' => SORT_DESC])->offset($offset)->limit($per_page)->asArray()->all();
        }

        return [
            'TokenRecords' => $resp,
            'pagination'   => [
                'page'     => $page,
                'per_page' => $per_page,
                'total'    => intval($total)
            ]
        ];
    }

    /**
     * 获取单条Token记录
     * @param $params
     * @return array|null
     * @throws ErrException
     */
    public static function detail($params)
    {
        $id = self::getId($params);
        if (!$id) {
            throw new ErrException(Code::PARAMS_ERROR, '请提供记录ID');
        }

        $data = SuiteCorpTokenRecord::find()->where(['id' => $id])->asArray()->one();
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST, '记录不存在');
        }

        return $data;
    }

    /**
     * @param $params
     * @return array
     * @throws ErrException
     */
    public static function items($params)
    {
        list($page, $per_page) = self::getPageInfo($params);
        $query = SuiteCorpTokenRecord::find();

        // 企业信息
        if ($corpKeyword = self::getString($params, 'corp_keyword')) {
            $query->andWhere(['Exists',
                SuiteCorpConfig::find()
                               ->andWhere(SuiteCorpConfig::tableName() . ".suite_id=" . SuiteCorpTokenRecord::tableName() . ".suite_id")
                               ->andWhere(SuiteCorpConfig::tableName() . ".corp_id=" . SuiteCorpTokenRecord::tableName() . ".corp_id")
                               ->andWhere([
                                   "OR",
                                   ['=', 'corp_id', $corpKeyword],
                                   ['=', 'corp_name', $corpKeyword],
                               ])
            ]);
        }

        // 分析类型
        if ($analysisType = self::getInt($params, 'analysis_type')) {
            $query->andWhere(['analysis_type' => $analysisType]);
        }

        // 分析日期范围
        if ($startDate = self::getString($params, 'analysis_date_start')) {
            $query->andWhere(['>=', 'analysis_date', $startDate]);
        }

        if ($endDate = self::getString($params, 'analysis_date_end')) {
            $query->andWhere(['<=', 'analysis_date', $endDate]);
        }

        $query->select(['id', 'suite_id', 'corp_id', 'current_tokens', 'surplus_tokens', 'input_tokens', 'output_tokens', 'analysis_type', 'analysis_time']);

        $total  = $query->count();
        $offset = ($page - 1) * $per_page;
        $resp   = [];

        if ($total > 0) {
            $resp = $query->with('suiteCorpConfig')->orderBy(['id' => SORT_DESC])->offset($offset)->limit($per_page)->asArray()->all();
        }

        return [
            'TokenRecords' => $resp,
            'pagination'   => [
                'page'     => $page,
                'per_page' => $per_page,
                'total'    => intval($total)
            ]
        ];
    }


}