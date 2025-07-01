<?php

namespace app\controllers;

use Aliyun\OTS\Consts\AggregationTypeConst;
use Aliyun\OTS\Consts\GroupByTypeConst;
use Aliyun\OTS\Consts\QueryTypeConst;
use Aliyun\OTS\Consts\SortOrderConst;
use Carbon\Carbon;
use common\components\AppController;
use common\components\BaseController;
use common\errors\Code;
use common\helpers\Format;
use common\models\Account;
use common\models\concerns\widgets\Account\Sql;
use common\models\crm\SuiteCorpCrmBusinessOpportunities;
use common\models\crm\SuiteCorpCrmBusinessOpportunitiesLink;
use common\models\crm\SuiteCorpCrmCustomerContact;
use common\models\SuiteAccountConfig;
use common\models\SuiteCorpAccountsDepartment;
use common\models\SuiteCorpDepartment;
use common\models\SuiteCorpExternalContact;
use common\models\SuiteCorpExternalContactFollowUser;
use common\models\SuiteCorpHitMsg;
use common\sdk\TableStoreChain;
use common\services\dashboard\concerns\MessageCount;
use common\services\dashboard\DashboardService;
use Illuminate\Support\Str;
use Toolkit\Stdlib\Arr;
use yii\db\Expression;

class DashboardController extends BaseController
{
    /**
     * 今日营收
     * path: /dashboard/today-revenue
     */
    public function actionTodayRevenue()
    {
        $suiteId = auth()->suiteId();
        $corpId  = auth()->corpId();

        // 需要查出今日和昨日的数据
        $todayStart     = Carbon::now()->startOfDay()->getTimestamp();
        $todayEnd       = Carbon::now()->endOfDay()->getTimestamp();
        $yesterdayStart = Carbon::yesterday()->startOfDay()->getTimestamp();
        $yesterdayEnd   = Carbon::yesterday()->endOfDay()->getTimestamp();

        $query = SuiteCorpCrmBusinessOpportunities::find()
            ->andWhere(['suite_id' => $suiteId])
            ->andWhere(['corp_id' => $corpId])
            ->andWhere(['!=', 'status', 3]);

        $sales = (clone $query)
            ->andWhere(['between', 'created_at', $todayStart, $todayEnd])
            ->sum('estimate_sale_money ');

        $yesterdaySales = (clone $query)
            ->andWhere(['between', 'created_at', $yesterdayStart, $yesterdayEnd])
            ->sum('estimate_sale_money ');

        $receipt = (clone $query)
            ->andWhere(['between', 'created_at', $todayStart, $todayEnd])
            ->sum('order_money ');

        $yesterdayReceipt = (clone $query)
            ->andWhere(['between', 'created_at', $yesterdayStart, $yesterdayEnd])
            ->sum('order_money ');

        return $this->responseSuccess([
            // 销售额
            'sales'         => $sales,
            // 销售额日环比
            'sales_ratio'   => Format::cycleRate($sales, $yesterdaySales),
            // 回款
            'receipt'       => $receipt,
            // 回款日环比
            'receipt_ratio' => Format::cycleRate($receipt, $yesterdayReceipt),
        ]);
    }

    /**
     * 实时商机
     * path: /dashboard/real-time-business
     */
    public function actionRealTimeBusiness()
    {
        $time = Carbon::now()->subHour(7)->getTimestamp();

        $data = SuiteCorpCrmBusinessOpportunities::find()
            ->select([
                new Expression('DATE_FORMAT(FROM_UNIXTIME(created_at), "%H:00") as hour'),
                'level',
                new Expression('count(*) as `count`')
            ])
            ->andWhere(['suite_id' => auth()->suiteId()])
            ->andWhere(['corp_id' => auth()->corpId()])
            ->andWhere(['>', 'created_at', $time])
            ->groupBy(['hour', 'level'])
            ->asArray()
            ->all();

        $data = collect($data)->groupBy('hour')
            ->map(function ($items, $hour) {
                $data         = [];
                $data['time'] = $hour;

                foreach ($items as $item) {
                    if (!$item['level']) {
                        $item['level'] = '无';
                    }

                    $data[$item['level']] = $item['count'];
                }

                return $data;
            })
            ->values()
            ->toArray();

        return $this->responseSuccess($data);
    }

    /**
     * 客户新增
     * path: /dashboard/customer-increase
     */
    public function actionCustomerIncrease()
    {
        $time    = Carbon::now()->subHour(7)->getTimestamp();
        $suiteId = auth()->suiteId();
        $corpId  = auth()->corpId();

        // 联系人
        $contacts = SuiteCorpCrmCustomerContact::find()
            ->select([
                new Expression('DATE_FORMAT(FROM_UNIXTIME(created_at), "%H:00") as hour'),
                new Expression('count(*) as `count`')
            ])
            ->andWhere(['suite_id' => $suiteId])
            ->andWhere(['corp_id' => $corpId])
            ->andWhere(['>', 'created_at', $time])
            ->groupBy('hour')
            ->indexBy('hour')
            ->asArray()
            ->all();

        // 好友
        $friends = SuiteCorpExternalContact::find()
            ->select([
                new Expression('DATE_FORMAT(FROM_UNIXTIME(created_at), "%H:00") as hour'),
                new Expression('count(*) as `count`')
            ])
            ->andWhere(['suite_id' => $suiteId])
            ->andWhere(['corp_id' => $corpId])
            ->andWhere(['>', 'created_at', $time])
            ->groupBy('hour')
            ->indexBy('hour')
            ->asArray()
            ->all();

        $data = [];

        foreach ($contacts as $hour => $contact) {
            Arr::set($data, "{$hour}.time", $hour);
            Arr::set($data, "{$hour}.联系人", $contact['count']);
            Arr::set($data, "{$hour}.好友", 0);
        }

        foreach ($friends as $hour => $friend) {
            Arr::set($data, "{$hour}.time", $hour);
            Arr::set($data, "{$hour}.好友", $friend['count']);

            if (!Arr::has($data, "{$hour}.联系人")) {
                Arr::set($data, "{$hour}.联系人", 0);
            }
        }

        $data = array_values($data);

        return $this->responseSuccess($data);
    }

    /**
     * 客户流失
     * path: /dashboard/customer-loss
     *
     * 2025-05-20: 经产品确认, 做成商机的流失(作废), 统计流失商机的数量和金额
     */
    public function actionCustomerLoss()
    {
        $time    = Carbon::now()->subHour(7)->getTimestamp();
        $suiteId = auth()->suiteId();
        $corpId  = auth()->corpId();

        $data = SuiteCorpCrmBusinessOpportunities::find()
            ->select([
                new Expression('DATE_FORMAT(updated_at, "%H:00") as time'),
                new Expression('count(*) as `count`'),
                new Expression('sum(estimate_sale_money) as `money`')
            ])
            ->andWhere(['suite_id' => $suiteId])
            ->andWhere(['corp_id' => $corpId])
            ->andWhere(['>', 'created_at', $time])
            ->andWhere(['status' => 3])
            ->groupBy('time')
            ->asArray()
            ->all();

        return $this->responseSuccess($data);
    }

    /**
     * 商机转化
     * path: /dashboard/business-conversion
     */
    public function actionBusinessConversion()
    {
        $time = Carbon::now()
            ->startOfDay()
            ->getTimestamp();


        $data = SuiteCorpCrmBusinessOpportunities::find()
            ->with(['industry', 'sop'])
            ->andWhere(['>', 'created_at', $time])
            ->asArray()
            ->all();

        $data = collect($data)
            ->groupBy('industry_no')
            ->map(function ($items, $industryNo) {
                $steps = $items->pluck('sop.content')
                    ->map(function ($content) {
                        return json_decode($content, true);
                    })
                    ->pluck('items')
                    ->flatten(1)
                    ->where('is_current_step', true)
                    ->sortBy('sort')
                    ->groupBy('name')
                    ->map(function ($items, $name) {
                        return count($items);
                    })
                    ->all();

                return array_merge(
                    [
                        'name'     => $items[0]['industry']['name'],
                        '新增商机' => count($items),
                    ],
                    $steps
                );
            })
            ->values()
            ->all();


        //        $data = [
        //            [
        //                'name'     => '商标服务',
        //                '新增商机' => 1,
        //                '沟通阶段' => 1,
        //            ],
        //            [
        //                'name'     => '企业服务',
        //                '新增商机' => 1,
        //                '沟通阶段' => 1,
        //            ]
        //        ];

        return $this->responseSuccess($data);
    }

    /**
     * 客户透视
     * path: /dashboard/customer-pivot
     */
    public function actionCustomerPivot()
    {
        $industryNo = $this->input('industry_no');
        $start      = $this->input('start');
        $end        = $this->input('end');
        $suiteId    = auth()->suiteId();
        $corpId     = auth()->corpId();

        $start = Carbon::parse($start);
        $end   = Carbon::parse($end);


        $data = SuiteCorpCrmBusinessOpportunities::find()
            ->alias('bo')
            ->select([
                new Expression('count(bo.id) as `new_opportunity`'),
                new Expression('sum(if(bo.status!=3, bo.estimate_sale_money, 0)) as `sales_amount`'),
                new Expression('sum(if(bo.status!=3, bo.order_money, 0)) as `payment_collected`'),
                new Expression('sum(if(bo.status=3, 1, 0)) as lost_opportunity'),
            ])
            // 权限控制
            ->when(!auth()->isCorpAdmin(), function ($query) {
                $query->andWhere([
                    'exists',
                    SuiteCorpCrmBusinessOpportunitiesLink::find()
                        ->alias('lk')
                        ->andWhere('bo.business_opportunities_no=lk.business_opportunities_no')
                        ->andWhere(['lk.account_id' => auth()->getStaffUnderling()]),
                ]);
            })
            ->when($industryNo, function ($query) use ($industryNo) {
                return $query->andWhere(['bo.industry_no' => $industryNo]);
            })
            ->andWhere(['bo.corp_id' => $corpId])
            ->andWhere(['bo.suite_id' => $suiteId])
            ->andWhere(['>', 'bo.created_at', $start->startOfDay()->getTimestamp()])
            ->andWhere(['<', 'bo.created_at', $end->endOfDay()->getTimestamp()])
            ->asArray()
            ->one();

        $previousEnd = clone $start;
        $previousEnd->subDay();

        $diff          = $start->diffInDays($end);
        $previousStart = clone $previousEnd;
        $previousStart->subDays($diff);

        $previousData = SuiteCorpCrmBusinessOpportunities::find()
            ->alias('bo')
            ->select([
                new Expression('count(bo.id) as `new_opportunity`'),
                new Expression('sum(if(bo.status!=3, bo.estimate_sale_money, 0)) as `sales_amount`'),
                new Expression('sum(if(bo.status!=3, bo.order_money, 0)) as `payment_collected`'),
                new Expression('sum(if(bo.status=3, 1, 0)) as lost_opportunity'),
            ])
            // 权限控制
            ->when(!auth()->isCorpAdmin(), function ($query) {
                $query->andWhere([
                    'exists',
                    SuiteCorpCrmBusinessOpportunitiesLink::find()
                        ->alias('lk')
                        ->andWhere('bo.business_opportunities_no=lk.business_opportunities_no')
                        ->andWhere(['lk.account_id' => auth()->getStaffUnderling()]),
                ]);
            })
            ->when($industryNo, function ($query) use ($industryNo) {
                return $query->andWhere(['bo.industry_no' => $industryNo]);
            })
            ->andWhere(['bo.corp_id' => $corpId])
            ->andWhere(['bo.suite_id' => $suiteId])
            ->andWhere(['>', 'bo.created_at', $previousStart->startOfDay()->getTimestamp()])
            ->andWhere(['<', 'bo.created_at', $previousEnd->endOfDay()->getTimestamp()])
            ->asArray()
            ->one();

        $data['new_opportunity_ratio']   = Format::cycleRate($data['new_opportunity'], $previousData['new_opportunity']);
        $data['sales_amount_ratio']      = Format::cycleRate($data['sales_amount'], $previousData['sales_amount']);
        $data['payment_collected_ratio'] = Format::cycleRate($data['payment_collected'], $previousData['payment_collected']);
        // 流失商机占比
        $data['lost_opportunity_ratio'] = Format::cycleRate($data['lost_opportunity'], $data['new_opportunity']);

        return $this->responseSuccess($data);
    }

    /**
     * 客户分布情况
     * path: /dashboard/customer-distribution
     */
    public function actionCustomerDistribution()
    {
        // $time    = Carbon::now()->subDays(30)->getTimestamp();
        $suiteId = auth()->suiteId();
        $corpId  = auth()->corpId();
        $start      = $this->input('start');
        $end        = $this->input('end');

        $start = Carbon::parse($start);
        $end   = Carbon::parse($end);

        $data = SuiteCorpCrmBusinessOpportunities::find()
            ->alias('bo')
            ->select([
                'bo.level',
                new Expression('count(distinct bo.id) as `count`'),
                new Expression('group_concat(distinct cct.tag_name) as `tags`'),
            ])
            ->leftJoin('suite_corp_crm_business_opportunities_contact as boc', 'boc.business_opportunities_no = bo.business_opportunities_no')
            ->leftJoin('suite_corp_crm_customer_contact_tags as cct', 'cct.contact_no = boc.contact_no')
            ->andWhere(['bo.corp_id' => $corpId])
            ->andWhere(['bo.suite_id' => $suiteId])
            ->andWhere(['>', 'bo.created_at', $start->startOfDay()->getTimestamp()])
            ->andWhere(['<', 'bo.created_at', $end->endOfDay()->getTimestamp()])
            ->groupBy('bo.level')
            ->asArray()
            ->all();

        $total = array_sum(array_column($data, 'count'));

        $data = array_map(function ($item) use ($total) {
            $item['ratio'] = Format::cycleRate($item['count'], $total);
            $item['total'] = $total;
            return $item;
        }, $data);

        return $this->responseSuccess($data);
    }

    /**
     * 商机趋势(近30天)
     * path: /dashboard/business-trend
     */
    public function actionBusinessTrend()
    {
        $suiteId = auth()->suiteId();
        $corpId  = auth()->corpId();
        $start   = $this->input('start');
        $end     = $this->input('end');

        $start = Carbon::parse($start);
        $end   = Carbon::parse($end);

        $data = SuiteCorpCrmBusinessOpportunities::find()
            ->alias('bo')
            ->select([
                new Expression('DATE_FORMAT(FROM_UNIXTIME(bo.created_at), "%m-%d") as `date`'),
                new Expression('count(bo.id) as `total`'),
                new Expression('sum(if(bo.status=2, 1, 0)) as `transform`'),
                new Expression('sum(if(bo.status=3, 1, 0)) as lost'),
            ])
            // 权限控制
            ->when(!auth()->isCorpAdmin(), function ($query) {
                $query->andWhere([
                    'exists',
                    SuiteCorpCrmBusinessOpportunitiesLink::find()
                        ->alias('lk')
                        ->andWhere('bo.business_opportunities_no=lk.business_opportunities_no')
                        ->andWhere(['lk.account_id' => auth()->getStaffUnderling()]),
                ]);
            })
            ->andWhere(['bo.corp_id' => $corpId])
            ->andWhere(['bo.suite_id' => $suiteId])
            ->andWhere(['>', 'bo.created_at', $start->startOfDay()->getTimestamp()])
            ->andWhere(['<', 'bo.created_at', $end->endOfDay()->getTimestamp()])
            ->groupBy('`date`')
            ->asArray()
            ->all();

        return $this->responseSuccess($data);
    }


    /**
     * 竞品情况统计
     * path: /dashboard/competitor-analysis
     */
    public function actionCompetitorAnalysis()
    {
        $suiteId    = auth()->suiteId();
        $corpId     = auth()->corpId();
        $start      = $this->input('start');
        $end        = $this->input('end');
        $industryNo = $this->input('industry_no');

        $start = Carbon::parse($start);
        $end   = Carbon::parse($end);

        $data = SuiteCorpCrmBusinessOpportunities::find()
            ->alias('bo')
            ->leftJoin('suite_corp_crm_business_opportunities_competition as co', 'co.business_opportunities_no = bo.business_opportunities_no')
            ->select([
                'co.name',
                // 统计 co.id 不为空的情况
                new Expression('count(co.id) as `total`'),
                new Expression('sum(if(bo.status=3, 1, 0)) as `loss`'),
            ])
            ->when($industryNo, function ($query) use ($industryNo) {
                return $query->andWhere(['bo.industry_no' => $industryNo]);
            })
            ->andWhere(['bo.suite_id' => $suiteId])
            ->andWhere(['bo.corp_id' => $corpId])
            ->andWhere(['>', 'bo.created_at', $start->startOfDay()->getTimestamp()])
            ->andWhere(['<', 'bo.created_at', $end->endOfDay()->getTimestamp()])
            ->andWhere('co.id is not null')
            ->groupBy('co.name')
            ->asArray()
            ->all();

        return $this->responseSuccess($data);
    }

    /**
     * 竞品优势解析
     * path: /dashboard/competitor-advantage
     */
    public function actionCompetitorAdvantage()
    {
        $suiteId    = auth()->suiteId();
        $corpId     = auth()->corpId();
        $start      = $this->input('start');
        $end        = $this->input('end');
        $industryNo = $this->input('industry_no');

        $start = Carbon::parse($start);
        $end   = Carbon::parse($end);

        $data = SuiteCorpCrmBusinessOpportunities::find()
            ->alias('bo')
            ->leftJoin('suite_corp_crm_business_opportunities_competition as co', 'co.business_opportunities_no = bo.business_opportunities_no')
            ->select([
                'co.name',
                new Expression('group_concat(co.advantage) as `advantages`'),
                new Expression('count(co.id) as `total`'),
            ])
            ->when($industryNo, function ($query) use ($industryNo) {
                return $query->andWhere(['bo.industry_no' => $industryNo]);
            })
            ->andWhere(['bo.suite_id' => $suiteId])
            ->andWhere(['bo.corp_id' => $corpId])
            ->andWhere(['>', 'bo.created_at', $start->startOfDay()->getTimestamp()])
            ->andWhere(['<', 'bo.created_at', $end->endOfDay()->getTimestamp()])
            ->andWhere('co.id is not null')
            ->groupBy('co.name')
            ->asArray()
            ->all();

        return $this->responseSuccess($data);
    }


    /**
     * 工作量排行
     * path: /dashboard/workload-rank
     */
    public function actionWorkloadRank()
    {
        $chain   = new TableStoreChain();
        $client  = $chain->OtsCreateClient();
        $suiteId = auth()->suiteId();
        $corpId  = auth()->corpId();
        $start   = $this->input('start');
        $end     = $this->input('end');

        $start = Carbon::parse($start);
        $end   = Carbon::parse($end);

        $result = MessageCount::make()
            ->start($start)
            ->end($end)
            ->corpId($corpId)
            ->suiteId($suiteId)
            ->execute();

        $sender = $result['sender'];

        $receiver = $result['receiver'];

        // 然后查出部门信息, 找出部门相关人员ID
        $departments = DashboardService::make()->getDepartmentsWithChildren();

        foreach ($departments as &$department) {
            $ids = array_merge([$department['id'], array_column($department['children'], 'id')]);

            $userIds = SuiteCorpAccountsDepartment::find()
                ->select('userid')
                ->andWhere(['department_id' => $ids])
                ->andWhere(['suite_id' => $suiteId])
                ->andWhere(['corp_id' => $corpId])
                ->column();

            // 发送消息数
            $department['send_total'] = collect($sender)->whereIn('key', $userIds)->sum('row_count');
            // 接收消息数
            $department['receive_total'] = collect($receiver)->whereIn('key', $userIds)->sum('row_count');
            // 好友总数
            $department['friend_total'] = SuiteCorpExternalContactFollowUser::find()->where(['userid' => $userIds])->count();
        }

        return $this->responseSuccess(array_values($departments));
    }

    /**
     * 客户跟进
     * path: /dashboard/customer-follow
     */
    public function actionCustomerFollow()
    {
        // 新增商机
        // 已汇款销售额
        // 会话次数
        // 风险触发
        $accountId = auth()->accountId();
        $suiteId   = auth()->suiteId();
        $corpId    = auth()->corpId();
        $start     = $this->input('start');
        $end       = $this->input('end');
        $userId    = auth()->account()['userid'];

        $start = Carbon::parse($start);
        $end   = Carbon::parse($end);

        $previousEnd = clone $start;
        $previousEnd->subDay();

        $diff          = $start->diffInDays($end);
        $previousStart = clone $previousEnd;
        $previousStart->subDays($diff);

        // 新增商机
        $newBusinessOpportunities = SuiteCorpCrmBusinessOpportunities::find()
            ->alias('bo')
            ->leftJoin('suite_corp_crm_business_opportunities_link as lk', 'lk.business_opportunities_no = bo.business_opportunities_no and lk.relational = 1')
            ->select([
                new Expression('count(bo.id) as total_opportunities'),
                new Expression('sum(bo.order_money) as payment_collected'),
            ])
            ->andWhere(['bo.suite_id' => $suiteId])
            ->andWhere(['bo.corp_id' => $corpId])
            ->andWhere(['>', 'bo.created_at', $start->startOfDay()->getTimestamp()])
            ->andWhere(['<', 'bo.created_at', $end->endOfDay()->getTimestamp()])
            ->andWhere(['lk.account_id' => $accountId])
            ->asArray()
            ->one();

        // 上期商机
        $previousBusinessOpportunities = SuiteCorpCrmBusinessOpportunities::find()
            ->alias('bo')
            ->leftJoin('suite_corp_crm_business_opportunities_link as lk', 'lk.business_opportunities_no = bo.business_opportunities_no and lk.relational = 1')
            ->select([
                new Expression('count(bo.id) as total_opportunities'),
                new Expression('sum(bo.order_money) as payment_collected'),
            ])
            ->andWhere(['bo.suite_id' => $suiteId])
            ->andWhere(['bo.corp_id' => $corpId])
            ->andWhere(['>', 'bo.created_at', $previousStart->startOfDay()->getTimestamp()])
            ->andWhere(['<', 'bo.created_at', $previousEnd->endOfDay()->getTimestamp()])
            ->andWhere(['lk.account_id' => $accountId])
            ->asArray()
            ->one();

        // 会话次数
        $sessionCount = SuiteCorpExternalContactFollowUser::find()
            ->andWhere([
                'exists',
                SuiteCorpExternalContact::find()
                    ->andWhere(['suite_id' => $suiteId])
                    ->andWhere(['corp_id' => $corpId])
                    ->andWhere('suite_corp_external_contact.id = suite_corp_external_contact_follow_user.external_contact_id')
            ])
            ->andWhere(['>', 'createtime', $start->startOfDay()->getTimestamp()])
            ->andWhere(['<', 'createtime', $end->endOfDay()->getTimestamp()])
            ->andWhere(['userid' => $userId])
            ->count();

        // 上期会话次数
        $previousSessionCount = SuiteCorpExternalContactFollowUser::find()
            ->andWhere([
                'exists',
                SuiteCorpExternalContact::find()
                    ->andWhere(['suite_id' => $suiteId])
                    ->andWhere(['corp_id' => $corpId])
                    ->andWhere('suite_corp_external_contact.id = suite_corp_external_contact_follow_user.external_contact_id')
            ])
            ->andWhere(['>', 'createtime', $previousStart->startOfDay()->getTimestamp()])
            ->andWhere(['<', 'createtime', $previousEnd->endOfDay()->getTimestamp()])
            ->andWhere(['userid' => $userId])
            ->count();

        // 风险触发次数
        $riskTriggerCount = SuiteCorpHitMsg::find()
            ->andWhere(['suite_id' => $suiteId])
            ->andWhere(['corp_id' => $corpId])
            ->andWhere(['>', 'updated_at', $start->startOfDay()->getTimestamp()])
            ->andWhere(['<', 'updated_at', $end->endOfDay()->getTimestamp()])
            ->andWhere([
                'OR',
                [
                    'AND',
                    ['sender_id' => $userId],
                    ['sender_type' => 1]
                ],
                [
                    'AND',
                    ['receiver_id' => $userId],
                    ['receiver_type' => 1]
                ]
            ])
            ->count();

        return $this->responseSuccess([
            'new_business_opportunities'      => $newBusinessOpportunities['total_opportunities'],
            'previous_business_opportunities' => $previousBusinessOpportunities['total_opportunities'],
            'business_opportunities_ratio'    => Format::cycleRate($newBusinessOpportunities['total_opportunities'], $previousBusinessOpportunities['total_opportunities']),
            'payment_collected'               => $newBusinessOpportunities['payment_collected'],
            'previous_payment_collected'      => $previousBusinessOpportunities['payment_collected'],
            'payment_collected_ratio'         => Format::cycleRate($newBusinessOpportunities['payment_collected'], $previousBusinessOpportunities['payment_collected']),
            'session_count'                   => $sessionCount,
            'previous_session_count'          => $previousSessionCount,
            'session_count_ratio'             => Format::cycleRate($sessionCount, $previousSessionCount),
            'risk_trigger_count'              => $riskTriggerCount,
        ]);
    }

    /**
     * 风险触发
     * path: /dashboard/risk-trigger
     */
    public function actionRiskTrigger()
    {
        $suiteId     = auth()->suiteId();
        $corpId      = auth()->corpId();
        $departments = DashboardService::make()->getDepartmentsOfStaffQuality();

        $paginator = Account::find()
            ->alias('a')
            ->select([
                'a.id',
                'a.nickname',
                'a.userid',
                Sql::redPacketReceiveCount(),
                Sql::triggerSensitiveWordCount(),
                Sql::sendBankCardCount(),
                Sql::sendIdCardCount(),
                Sql::lostBusinessCount(),
            ])
            ->andWhere(['suite_id' => $suiteId])
            ->andWhere(['corp_id' => $corpId])
            ->andWhere([
                'exists',
                SuiteCorpDepartment::find()
                    ->alias('dept')
                    ->leftJoin('suite_corp_accounts_department as ad', 'ad.department_id = dept.department_id')
                    ->andWhere(value(function () use ($departments) {
                        $paths = array_column($departments, 'path');

                        $data   = [];
                        $data[] = 'OR';

                        $data[] = ['in', 'dept.path', $paths];
                        foreach ($paths as $path) {
                            $data[] = ['like', 'dept.path', $path . '-%', false];
                        }
                        return $data;
                    }))
                    ->andWhere('ad.userid=a.userid')
            ])
            ->asArray()
            ->paginate($this->input('per_page', 20));


        return $this->responsePaginator($paginator);
    }

    /**
     * 我的卡片
     * path: /dashboard/my-card
     */
    public function actionMyCard()
    {
        $suiteId = auth()->suiteId();
        $corpId  = auth()->corpId();
        $account = auth()->account();

        $departmentId = SuiteCorpAccountsDepartment::corp()
            ->select('department_id')
            ->andWhere(['userid' => $account['userid']])
            ->orderBy(['is_leader_in_dept' => SORT_ASC])
            ->scalar();

        $messageCount = MessageCount::make()
            ->corpId($corpId)
            ->suiteId($suiteId)
            ->start(Carbon::today()->startOfDay())
            ->end(Carbon::tomorrow()->startOfDay())
            ->execute();

        $opportunities = SuiteCorpCrmBusinessOpportunities::find()
            ->alias('bo')
            ->leftJoin('suite_corp_crm_business_opportunities_link as lk', 'lk.business_opportunities_no = bo.business_opportunities_no and lk.relational = 1')
            ->select([
                new Expression('count(bo.id) as total'),
                new Expression('sum(if(bo.status = 2, 1, 0)) as transform'),
            ])
            ->andWhere(['bo.suite_id' => $suiteId])
            ->andWhere(['bo.corp_id' => $corpId])
            ->andWhere(['>', 'bo.created_at', Carbon::today()->startOfDay()->startOfDay()->getTimestamp()])
            ->andWhere(['<', 'bo.created_at', Carbon::tomorrow()->startOfDay()->endOfDay()->getTimestamp()])
            ->andWhere(['lk.account_id' => $account['id']])
            ->asArray()
            ->one();
        
        $data = [
            'userid'           => $account['userid'],
            // 负责部门数
            'department_count' => SuiteCorpAccountsDepartment::corp()
                ->andWhere(['userid' => $account['userid']])
                ->andWhere(['is_leader_in_dept' => 1])
                ->count(),
            // 所在部门
            'department'       => SuiteCorpDepartment::corp()
                ->andWhere(['department_id' => $departmentId])
                ->asArray()
                ->one(),
            // 今日消息条数
            'message_count'    => collect($messageCount['sender'])->where('key', $account['userid'])->sum('row_count'),
            // 今日新增商机
            'business_count'   => $opportunities['total'],
            // 转化率
            'transform_rate' => Format::rate($opportunities['transform'], $opportunities['total']),
        ];

        return $this->responseSuccess($data);
    }
}
