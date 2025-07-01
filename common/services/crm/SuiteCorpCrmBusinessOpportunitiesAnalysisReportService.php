<?php

namespace common\services\crm;

use common\errors\Code;
use common\errors\ErrException;
use common\models\analysis\SuiteCorpAnalysisTaskDate;
use common\models\analysis\SuiteCorpAnalysisTaskResultDetails;
use common\models\crm\SuiteCorpCrmBusinessOpportunities;
use common\models\crm\SuiteCorpCrmBusinessOpportunitiesAnalysisReport;
use common\models\crm\SuiteCorpCrmCustomerContact;
use common\models\crm\SuiteCorpCrmCustomerContactInformation;
use common\services\BusinessOpportunitiesAnalysisService;
use common\services\Service;

class SuiteCorpCrmBusinessOpportunitiesAnalysisReportService extends Service
{
    /**
     * 分析报告列表
     * @param array $params
     * @return array|null
     */
    public static function index(array $params)
    {
        return SuiteCorpCrmBusinessOpportunitiesAnalysisReport::corp()
            ->select([
                SuiteCorpCrmBusinessOpportunitiesAnalysisReport::asField('id'),
                SuiteCorpCrmBusinessOpportunitiesAnalysisReport::asField('business_opportunities_no'),
                SuiteCorpCrmBusinessOpportunitiesAnalysisReport::asField('created_date'),
                SuiteCorpCrmBusinessOpportunitiesAnalysisReport::asField('follow_userid'),
                SuiteCorpCrmBusinessOpportunitiesAnalysisReport::asField('contact_userid'),
                SuiteCorpCrmBusinessOpportunitiesAnalysisReport::asField('contact_role'),
                SuiteCorpCrmBusinessOpportunitiesAnalysisReport::asField('created_at'),
                SuiteCorpCrmBusinessOpportunitiesAnalysisReport::asField('status'),
                SuiteCorpCrmBusinessOpportunitiesAnalysisReport::asField('last_msg_at'),
            ])
            ->andWhere([
                SuiteCorpCrmBusinessOpportunitiesAnalysisReport::asField('business_opportunities_no') => self::getString($params, 'business_opportunities_no'),
            ])
            ->when(self::getString($params, 'created_date'),function ($query, $value){
                $query->andWhere([SuiteCorpCrmBusinessOpportunitiesAnalysisReport::asField('created_date') => $value,]);
            })
            ->with([
                'information' => function ($query){
                    $query->select([
                        SuiteCorpCrmCustomerContactInformation::asField('id'),
                        SuiteCorpCrmCustomerContactInformation::asField('contact_number'),
                        SuiteCorpCrmCustomerContactInformation::asField('contact_no'),
                    ])
                        ->with([
                            'qwContact' => function ($query) {
                                $query->select([
                                    SuiteCorpCrmCustomerContact::asField('id'),
                                    SuiteCorpCrmCustomerContact::asField('contact_no'),
                                    SuiteCorpCrmCustomerContact::asField('contact_name'),
                                ]);
                            }
                        ]);
                },
            ])
            ->orderBy([
                SuiteCorpCrmBusinessOpportunitiesAnalysisReport::asField('created_at') => SORT_DESC,
            ])
            ->myPage($params);
    }

    /**
     * 角标数
     * @param array $params
     * @return bool|int|string|null
     */
    public static function subscript(array $params)
    {
        return SuiteCorpCrmBusinessOpportunitiesAnalysisReport::corp()
            ->andWhere([
                SuiteCorpCrmBusinessOpportunitiesAnalysisReport::asField('business_opportunities_no') => self::getString($params, 'business_opportunities_no'),
                SuiteCorpCrmBusinessOpportunitiesAnalysisReport::asField('state') => SuiteCorpCrmBusinessOpportunitiesAnalysisReport::STATE_2,
                SuiteCorpCrmBusinessOpportunitiesAnalysisReport::asField('status') => SuiteCorpCrmBusinessOpportunitiesAnalysisReport::STATUS_1,
            ])
            ->count();
    }

    /**
     * 商机分析报告详情
     * @param array $params
     * @return array
     * @throws ErrException
     */
    public static function info(array $params)
    {
        $id = self::getId($params);
        $report = SuiteCorpCrmBusinessOpportunitiesAnalysisReport::findOne($id);
        if (!$report) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '未找到该商机分析报告');
        }
        $report = $report->toArray();
        $report = SuiteCorpCrmBusinessOpportunitiesAnalysisReport::transform($report);
        $bo = SuiteCorpCrmBusinessOpportunities::findOne(['business_opportunities_no' => $report['business_opportunities_no']]);
        //查询会话分析记录
        $sessionTaskDate = SuiteCorpAnalysisTaskDate::findOne([
            'suite_id' => $bo->suite_id,
            'corp_id' => $bo->corp_id,
            'session_id' => $report['session_id'],
            'analysis_type' => SuiteCorpAnalysisTaskDate::ANALYSIS_TYPE_2,
            'analysis_date' => $report['created_date'],
        ]);
        $sessionTaskInfo = null;
        if ($sessionTaskDate){
            $sessionTaskInfo = SuiteCorpAnalysisTaskResultDetails::find()
                ->select(['result_key', 'result_value'])
                ->where(['task_id' => $sessionTaskDate->task_id,])
                ->asArray()
                ->all();
            $sessionTaskInfo = array_column($sessionTaskInfo, 'result_value', 'result_key');
        }
        $report['session_analysis'] = $sessionTaskInfo;
        $report['session_analysis_task_id'] = $sessionTaskDate->task_id ?? null;

        return $report;
    }

    /**
     * 应用报告
     * @param array $params
     * @return SuiteCorpCrmBusinessOpportunitiesAnalysisReport
     * @throws ErrException
     * @throws \yii\db\Exception
     */
    public static function usage(array $params)
    {
        $id = self::getId($params);
        $report = SuiteCorpCrmBusinessOpportunitiesAnalysisReport::findOne($id);
        if (!$report) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '未找到该商机分析报告');
        }
        $bo = SuiteCorpCrmBusinessOpportunities::findOne(['business_opportunities_no' => $report->business_opportunities_no]);
        if (!$bo){
             throw new ErrException(Code::BUSINESS_ABNORMAL, '未找到该商机');
        }
        $contactInformation = SuiteCorpCrmCustomerContactInformation::findOne(['contact_information_type' => SuiteCorpCrmCustomerContactInformation::CONTACT_INFORMATION_TYPE_4, 'contact_number' => $report->contact_userid,]);
        if (!$contactInformation) {
            throw new ErrException(Code::BUSINESS_ABNORMAL, '未找到联系人编号');
        }
        return BusinessOpportunitiesAnalysisService::used_report($report, $bo, $contactInformation->contact_no);
    }

}
