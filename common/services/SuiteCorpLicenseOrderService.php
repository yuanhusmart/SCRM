<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpConfig;
use common\models\SuiteCorpLicenseActiveInfo;
use common\models\SuiteCorpLicenseOrder;
use common\models\SuiteCorpLicenseOrderInfo;

/**
 * Class SuiteCorpLicenseOrderService
 * @package common\services
 */
class SuiteCorpLicenseOrderService extends Service
{

    const BUYER_USERID = 'XieYaLan';

    const CHANGE_FIELDS = ['suite_id', 'order_id', 'order_type', 'order_status', 'price', 'create_time', 'pay_time'];

    /**
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     */
    public static function create($params)
    {
        $params['suite_id'] = \Yii::$app->params["workWechat"]['suiteId'];
        $attributes         = self::includeKeys($params, self::CHANGE_FIELDS);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        if (empty($attributes['suite_id']) || empty($attributes['order_id']) || empty($attributes['order_type'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $licenseOrder = SuiteCorpLicenseOrder::findOne(['suite_id' => $attributes['suite_id'], 'order_id' => $attributes['order_id'], 'order_type' => $attributes['order_type']]);
            // 如果数据不存在 写入主表 + 媒体数据
            if (empty($licenseOrder)) {
                $licenseOrder = new SuiteCorpLicenseOrder();
                $licenseOrder->load($attributes, '');
                // 校验参数
                if (!$licenseOrder->validate()) {
                    throw new ErrException(Code::PARAMS_ERROR, $licenseOrder->getErrors());
                }
                if (!$licenseOrder->save()) {
                    throw new ErrException(Code::CREATE_ERROR, $licenseOrder->getErrors());
                }
                $licenseOrderId = $licenseOrder->getPrimaryKey();
            } else {
                $licenseOrderId           = $licenseOrder->id;
                $licenseOrder->attributes = $attributes;
                if (!$licenseOrder->save()) {
                    throw new ErrException(Code::UPDATE_ERROR, $licenseOrder->getErrors());
                }
            }
            if (!empty($params['buy_list'])) {
                foreach ($params['buy_list'] as $buyListItem) {
                    $buyListItem['license_order_id'] = $licenseOrderId;
                    SuiteCorpLicenseOrderInfoService::create($buyListItem);
                }
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        return $licenseOrderId;
    }

    /**
     * @param $params
     * @return array
     * @throws ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public static function items($params)
    {
        list($page, $per_page) = self::getPageInfo($params);
        $suiteId = self::getString($params, 'suite_id');
        if (!$suiteId) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $query = SuiteCorpLicenseOrder::find()->andWhere(["suite_id" => $suiteId]);
        // 主体
        if ($corpId = self::getString($params, 'corp_id')) {
            $query->andWhere(['Exists',
                SuiteCorpLicenseOrderInfo::find()
                                         ->select(SuiteCorpLicenseOrderInfo::tableName() . '.id')
                                         ->andWhere(SuiteCorpLicenseOrderInfo::tableName() . ".license_order_id=" . SuiteCorpLicenseOrder::tableName() . ".id")
                                         ->andWhere([SuiteCorpLicenseOrderInfo::tableName() . '.corp_id' => $corpId])
            ]);
        }
        // 订单状态
        if ($orderStatus = self::getInt($params, 'order_status')) {
            $query->andWhere(["order_status" => $orderStatus]);
        }
        $total  = $query->count();
        $offset = ($page - 1) * $per_page;
        $resp   = [];
        if ($total > 0) {
            $resp = $query->orderBy(['created_at' => SORT_DESC])->offset($offset)->limit($per_page)->all();
        }
        $data = [
            'LicenseOrder' => [],
            'pagination'   => [
                'page'     => $page,
                'per_page' => $per_page,
                'total'    => intval($total)
            ]
        ];
        foreach ($resp as $value) {
            $data['LicenseOrder'][] = $value->getItemsData();
        }
        return $data;
    }

    /**
     * @return true
     * @throws ErrException
     */
    public static function licenseOrderPull()
    {
        try {
            \Yii::warning('licenseOrderPull:获取服务商配置数据');
            $corpConfig = SuiteCorpConfig::find()->select('corp_id')->asArray()->all();
            foreach ($corpConfig as $config) {

                $params = ['corpid' => $config['corp_id'], 'limit' => 1000];
                $data   = SuiteService::licenseListOrder($params);

                if (empty($data['order_list'])) {
                    continue;
                }

                foreach ($data['order_list'] as $item) {
                    if ($item['order_type'] == SuiteCorpLicenseOrder::ORDER_TYPE_8) {
                        self::unionOrderPull($item);
                    } else {
                        self::orderPull($item);
                    }
                }
            }
        } catch (\Exception $e) {
            \Yii::warning($e->getMessage());
            throw new ErrException(Code::DATA_ERROR, $e->getMessage());
        }
        return true;
    }

    /**
     * 更新订单数据
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     */
    public static function orderPull($params)
    {
        $orderId = self::getString($params, 'order_id');
        if (!$orderId) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $order = SuiteService::licenseOrder($orderId);
        $order = $order['order'] ?? [];
        if (empty($order)) {
            throw new ErrException(Code::DATA_ERROR, '未获取到订单数据');
        }
        $order['buy_list'] = [$order];
        $return            = SuiteCorpLicenseOrderService::create($order);
        self::syncAccountByOrderId($orderId);
        return $return;
    }

    /**
     * 更新多企业订单数据
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     */
    public static function unionOrderPull($params)
    {
        $orderId = self::getString($params, 'order_id');
        if (!$orderId) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $order = SuiteService::licenseUnionOrder($orderId);
        if (empty($order)) {
            throw new ErrException(Code::DATA_ERROR, '未获取到多企业订单数据');
        }
        $subOrder = array_merge($order['order'], $order, $params);
        $return   = SuiteCorpLicenseOrderService::create($subOrder);
        self::syncAccountByOrderId($orderId);
        return $return;
    }

    /**
     * @param $params
     * @return mixed|string
     * @throws ErrException
     */
    public static function createNewOrder($params)
    {
        $params['buyer_userid'] = self::BUYER_USERID;
        if (empty($params['suite_id']) || empty($params['corpid']) || empty($params['buyer_userid']) || empty($params['account_count']) || empty($params['account_duration'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        if (empty($params['account_count']['base_count']) && empty($params['account_count']['external_contact_count'])) {
            throw new ErrException(Code::PARAMS_ERROR, '账号个数详情，基础账号跟互通账号不能同时为0');
        }
        if (empty($params['account_duration']['months']) && empty($params['account_duration']['days'])) {
            throw new ErrException(Code::PARAMS_ERROR, '购买的月数、购买的天数至少需要填写一个');
        }
        $resp = SuiteService::licenseCreateNewOrder(self::includeKeys($params, ['corpid', 'buyer_userid', 'account_count', 'account_duration']));
        if (!empty($resp['order_id'])) {
            self::orderPull($resp);
        }
        return $resp['order_id'] ?? '';
    }


    /**
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function submitNewOrderJob($params)
    {
        $params['buyer_userid'] = self::BUYER_USERID;
        if (empty($params['suite_id']) || empty($params['buy_list']) || empty($params['buyer_userid'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $create = SuiteService::licenseCreateNewOrderJob(['buy_list' => $params['buy_list']]);
        if (empty($create['jobid'])) {
            throw new ErrException(Code::PARAMS_ERROR, '创建失败未生成 多企业新购任务ID');
        }
        SuiteService::licenseSubmitNewOrderJob(['jobid' => $create['jobid'], 'buyer_userid' => $params['buyer_userid']]);
        $create['orderJobResult'] = SuiteService::licenseNewOrderJobResult($create['jobid']);
        if (!empty($create['orderJobResult']['order_id'])) {
            self::unionOrderPull($create['orderJobResult']);
        }
        return $create;
    }

    /**
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function createRenewOrderJob($params)
    {
        $params['buyer_userid'] = self::BUYER_USERID;
        if (empty($params['suite_id']) || empty($params['corpid']) || empty($params['account_list']) || empty($params['buyer_userid']) || empty($params['account_duration'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        if (count($params['account_list']) > 1000) {
            throw new ErrException(Code::PARAMS_ERROR, '续期的账号列表，每次最多1000个');
        }
        // 创建续期任务
        $create = SuiteService::licenseCreateRenewOrderJob(['corpid' => $params['corpid'], 'account_list' => $params['account_list']]);
        if (empty($create['jobid'])) {
            throw new ErrException(Code::PARAMS_ERROR, '创建失败未生成 多企业新购任务ID');
        }
        // 提交续期订单
        $submitOrderJob     = SuiteService::licenseSubmitOrderJob([
            'jobid'            => $create['jobid'],
            'buyer_userid'     => $params['buyer_userid'],
            'account_duration' => $params['account_duration']
        ]);
        $create['order_id'] = $submitOrderJob['order_id'];
        self::unionOrderPull($create);
        return $create;
    }

    /**
     * 绑定员工
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function bindActiveAccount($params)
    {
        if (empty($params['corpid']) || empty($params['userid']) || empty($params['active_code'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        return SuiteService::licenseActiveAccount(self::includeKeys($params, ['corpid', 'userid', 'active_code']));
    }

    /**
     * 批量绑定员工
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function bindBatchActiveAccount($params)
    {
        if (empty($params['corpid']) || empty($params['active_list'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $resp = SuiteService::licenseBatchActiveAccount(self::includeKeys($params, ['corpid', 'active_list']));
        return $resp['active_result'];
    }

    /**
     * 继承
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function batchTransferLicense($params)
    {
        if (empty($params['corpid']) || empty($params['transfer_list'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $resp           = SuiteService::licenseBatchTransferLicense($params['corpid'], $params['transfer_list']);
        $transferResult = empty($resp['transfer_result']) ? [] : $resp['transfer_result'];
        foreach ($transferResult as $result) {
            // 判断转移成功逻辑
            if (!empty($result['handover_userid']) && !empty($result['takeover_userid']) && empty($result['errcode'])) {
                SuiteCorpLicenseActiveInfoService::updateActiveTimeByCorpAndUserId(['corp_id' => $params['corpid'], 'userid' => $result['handover_userid']]);
            }
            $data = SuiteCorpLicenseActiveInfo::find()
                                              ->select('license_order_id,license_order_info_id,suite_id,corp_id,order_id,active_code')
                                              ->andWhere(['corp_id' => $params['corpid'], 'userid' => $result['handover_userid']])
                                              ->andWhere(['in', 'status', [SuiteCorpLicenseActiveInfo::STATUS_2, SuiteCorpLicenseActiveInfo::STATUS_4]])
                                              ->asArray()
                                              ->all();
            foreach ($data as $itemActiveInfo) {
                $activeInfo = SuiteService::licenseGetActiveInfoByCode($params['corpid'], $itemActiveInfo['active_code']);
                SuiteCorpLicenseActiveInfoService::create(array_merge($activeInfo['active_info'], $itemActiveInfo));
            }
        }
        return $transferResult;
    }

    /**
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function cancelOrder($params)
    {
        if (empty($params['order_id'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $resp         = SuiteService::licenseCancelOrder($params);
        $licenseOrder = SuiteCorpLicenseOrder::findOne(['order_id' => $params['order_id']]);
        if ($licenseOrder) {
            $licenseOrder->order_status = SuiteCorpLicenseOrder::ORDER_STATUS_2;
            if (!$licenseOrder->save()) {
                throw new ErrException(Code::UPDATE_ERROR, $licenseOrder->getErrors());
            }
        }
        return $resp;
    }

    /**
     * @param $orderId
     * @return true
     * @throws ErrException
     */
    public static function syncAccountByOrderId($orderId)
    {
        try {
            $orderItem = SuiteCorpLicenseOrderInfo::find()->alias('a')
                                                  ->select('a.id,a.license_order_id,a.corp_id,a.sub_order_id,b.suite_id,b.order_id')
                                                  ->leftJoin(SuiteCorpLicenseOrder::tableName() . ' AS b', 'a.license_order_id = b.id')
                                                  ->andWhere(['b.order_id' => $orderId])
                                                  ->andWhere(['b.order_status' => SuiteCorpLicenseOrder::ORDER_STATUS_1])
                                                  ->asArray()
                                                  ->all();

            if (!empty($orderItem)) {
                foreach ($orderItem as $item) {
                    // 获取订单中的账号列表 - 如果是多企业订单，请先调用获取多企业订单详情获取到每个企业的子订单id (sub_order_id)，然后使用 sub_order_id 来调用此接口。
                    if (!empty($item['sub_order_id'])) {
                        $orderId = $item['sub_order_id'];
                    }
                    $orderAccount = SuiteService::licenseListOrderAccount($orderId);
                    \Yii::warning('企业:' . $item['corp_id'] . ' , 账号数量：' . count($orderAccount['account_list']));
                    // 批量获取激活码详情
                    $activeInfo = SuiteService::licenseBatchGetActiveInfoByCode($item['corp_id'], array_column($orderAccount['account_list'], 'active_code'));
                    foreach ($activeInfo['active_info_list'] as $activeItem) {
                        $activeItem['license_order_id']      = $item['license_order_id'];
                        $activeItem['license_order_info_id'] = $item['id'];
                        $activeItem['suite_id']              = $item['suite_id'];
                        $activeItem['corp_id']               = $item['corp_id'];
                        $activeItem['order_id']              = $orderId;
                        SuiteCorpLicenseActiveInfoService::create($activeItem);
                    }
                }
            }
        } catch (\Exception $e) {
            throw new ErrException(Code::DATA_ERROR, $e->getMessage());
        }
        return true;
    }

}
