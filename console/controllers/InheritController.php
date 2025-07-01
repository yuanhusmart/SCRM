<?php

namespace console\controllers;


use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpConfig;
use common\models\SuiteCorpInherit;
use common\models\SuiteCorpInheritList;
use common\services\Service;
use common\services\SuiteCorpInheritListService;
use common\services\SuiteCorpInheritService;
use common\services\SuiteService;

class InheritController extends BaseConsoleController
{

    /**
     * 继承-待执行数据入队
     * 定时任务 每分钟执行1次
     * php ./yii inherit/push-queue
     * @return true
     */
    public function actionPushQueue()
    {
        self::consoleLog('继承-待执行数据入队-开始');
        try {
            self::consoleLog('获取服务商配置数据');
            $mqData = SuiteCorpInherit::find()->where(['status' => SuiteCorpInherit::INHERIT_STATUS_1])->select('id,inherit_type')->asArray()->all();
            // 批量推送到MQ
            if ($mqData) {
                $routingKey = SuiteCorpInheritService::MQ_INHERIT_ROUTING_KEY;
                Service::pushRabbitMQMsg(SuiteCorpInheritService::MQ_INHERIT_EXCHANGE, SuiteCorpInheritService::MQ_INHERIT_QUEUE, function ($mq) use ($mqData, $routingKey) {
                    try {
                        foreach ($mqData as $msg) {
                            $mq->publish(json_encode($msg, JSON_UNESCAPED_UNICODE), $routingKey);
                        }
                    } catch (\Exception $e) {
                        \Yii::warning($e->getMessage());
                    }
                }, $routingKey);
            }
        } catch (\Exception $e) {
            self::consoleLog($e->getMessage());
        }
        self::consoleLog('继承-待执行数据入队-结束');
        return true;
    }

    /**
     * 继承-查询执行状态- 测试
     * php ./yii inherit/query-status-demo
     * @return true
     */
    public function actionQueryStatusDemo($id = 0)
    {
        $item      = SuiteCorpInherit::find()->where(['id' => $id])->select('id,type,suite_id,corp_id')->asArray()->one();
        $config    = SuiteCorpConfig::find()->where(['suite_id' => $item['suite_id'], 'corp_id' => $item['corp_id']])->limit(1)->one();
        $data      = SuiteCorpInheritList::find()->andWhere(['inherit_id' => $item['id']])->one();
        $apiParams = ['handover_userid' => $data->userid, 'takeover_userid' => $data->heir];
        $curlData  = self::executeTransferResultByCurl($config, $item['type'], $apiParams);
        self::consoleLog('executeTransferResultByCurl - 接口结果 :' . json_encode($curlData, JSON_UNESCAPED_UNICODE));
        die;
    }

    /**
     * 继承-查询执行状态
     * 定时任务 每五分钟执行1次
     * php ./yii inherit/query-status
     * @return true
     */
    public function actionQueryStatus()
    {
        self::consoleLog('继承-查询状态-开始');
        try {
            self::consoleLog('获取执行中的继承数据');
            $inherit = SuiteCorpInherit::find()->where(['status' => SuiteCorpInherit::INHERIT_STATUS_2])->select('id,type,suite_id,corp_id')->asArray()->all();
            foreach ($inherit as $item) {
                $config = SuiteCorpConfig::find()->where(['suite_id' => $item['suite_id'], 'corp_id' => $item['corp_id']])->limit(1)->one();
                $data   = SuiteCorpInheritList::find()
                                              ->andWhere(['inherit_id' => $item['id']])
                                              ->select([
                                                  'inherit_id',
                                                  'COUNT(id) as counts',
                                                  'COUNT(IF(status=1,1,NULL)) as success_counts',
                                                  'COUNT(IF(status=2,1,NULL)) as wait_counts',
                                              ])
                                              ->asArray()
                                              ->one();
                self::consoleLog($data);
                if (!empty($data)) { // 进入主表执行状态逻辑 1.待执行 2.执行中 3.执行完毕
                    // 如果全部交接数据 大于 0 并且 等待交接数量 等于 0 ，则是 执行完毕
                    if ($data['counts'] > 0 && $data['wait_counts'] == 0) {
                        SuiteCorpInherit::updateAll(['status' => SuiteCorpInherit::INHERIT_STATUS_3, 'complete_at' => time(), 'updated_at' => time()], ['and', ['id' => $item['id']]]);
                    } else {
                        $data      = SuiteCorpInheritList::find()
                                                         ->andWhere(['inherit_id' => $item['id']])
                                                         ->andWhere(['type' => SuiteCorpInheritList::TYPE_1])
                                                         ->andWhere(['status' => SuiteCorpInheritList::STATUS_2])
                                                         ->one();
                        $apiParams = ['handover_userid' => $data->userid, 'takeover_userid' => $data->heir];
                        do {
                            if (!empty($nextCursor)) {
                                $apiParams['cursor'] = $nextCursor;
                            }
                            $curlData = self::executeTransferResultByCurl($config, $item['type'], $apiParams);
                            self::consoleLog('executeTransferResultByCurl - 接口结果 :' . json_encode($curlData, JSON_UNESCAPED_UNICODE));
                            foreach ($curlData['customer'] as $customer) {
                                try {
                                    self::consoleLog($customer);
                                    SuiteCorpInheritList::updateAll(['status' => $customer['status'], 'takeover_time' => $customer['takeover_time'] ?? 0, 'updated_at' => time()],
                                        [
                                            'and',
                                            ['inherit_id' => $item['id']],
                                            ['type' => SuiteCorpInheritList::TYPE_1],
                                            ['status' => SuiteCorpInheritList::STATUS_2],
                                            ['external_id' => $customer['external_userid']],
                                        ]);
                                } catch (\Exception $createE) {
                                    self::consoleLog($createE);
                                }
                            }
                            $nextCursor = $curlData['next_cursor'] ?? '';
                        } while ($nextCursor !== '');
                    }
                }
            }
        } catch (\Exception $e) {
            self::consoleLog($e->getMessage());
        }
        self::consoleLog('继承-查询状态-结束');
        return true;
    }

    /**
     * 执行继承-消费者
     * php ./yii inherit/execute-queue
     *
     * Supervisor:aaw.inherit.execute-queue [ supervisorctl restart aaw.inherit.execute-queue ]
     * Supervisor Log:/var/log/supervisor/aaw.inherit.execute-queue.log
     * @return void
     */
    public function actionExecuteQueue()
    {
        $routingKey = SuiteCorpInheritService::MQ_INHERIT_ROUTING_KEY;
        Service::consoleConsumptionMQ(SuiteCorpInheritService::MQ_INHERIT_EXCHANGE, SuiteCorpInheritService::MQ_INHERIT_QUEUE, function ($msg) use ($routingKey) {
            \Yii::$app->db->close();
            self::consoleLog($msg);
            try {
                $inherit = SuiteCorpInherit::findOne($msg['id']);
                self::consoleLog($inherit->toArray());
                if ($inherit->status != SuiteCorpInherit::INHERIT_STATUS_1) {
                    self::consoleLog('跳过ID：' . $inherit->id . ',状态非待执行：' . $inherit->status);
                } else {
                    /* 继承类型 1.继承客户 2.继承群 3.整体继承 */
                    switch ($inherit->inherit_type) {
                        case SuiteCorpInherit::INHERIT_TYPE_1: // 继承客户
                            self::executeTransferCustomer(
                                $inherit,
                                SuiteCorpInheritList::TYPE_1,
                                SuiteCorpInheritListService::getExternalIdByType($inherit->id, SuiteCorpInheritList::TYPE_1)
                            );
                        break;

                        case SuiteCorpInherit::INHERIT_TYPE_2: // 继承群
                            self::executeTransferCustomer(
                                $inherit,
                                SuiteCorpInheritList::TYPE_2,
                                SuiteCorpInheritListService::getExternalIdByType($inherit->id, SuiteCorpInheritList::TYPE_2)
                            );
                        break;

                        case SuiteCorpInherit::INHERIT_TYPE_3: // 整体继承
                        case SuiteCorpInherit::INHERIT_TYPE_4: // 客户及该客户相关群
                            self::executeTransferCustomer(
                                $inherit,
                                SuiteCorpInheritList::TYPE_1,
                                SuiteCorpInheritListService::getExternalIdByType($inherit->id, SuiteCorpInheritList::TYPE_1)
                            );

                            self::executeTransferCustomer(
                                $inherit,
                                SuiteCorpInheritList::TYPE_2,
                                SuiteCorpInheritListService::getExternalIdByType($inherit->id, SuiteCorpInheritList::TYPE_2)
                            );
                        break;

                        default :
                            throw new ErrException(Code::PARAMS_ERROR, '继承类型异常');
                    }
                    /* 主表状态调整为 执行中 */
                    $inherit->status = SuiteCorpInherit::INHERIT_STATUS_2;
                    $inherit->save();
                }
            } catch (\Exception $e) {
                self::consoleLog('执行继承-消费者：' . json_encode($msg, JSON_UNESCAPED_UNICODE) . ',跳过' . $e->getMessage());
                // 失败重新入队
                Service::pushRabbitMQMsg(SuiteCorpInheritService::MQ_INHERIT_EXCHANGE, SuiteCorpInheritService::MQ_INHERIT_QUEUE, function ($mq) use ($msg, $routingKey) {
                    try {
                        $mq->publish(json_encode($msg, JSON_UNESCAPED_UNICODE), $routingKey);
                    } catch (\Exception $e) {
                        \Yii::warning($e->getMessage());
                    }
                }, $routingKey);
            }
        }, 1, $routingKey);
    }

    /**
     * 执行转移 - 客户
     * @param $inherit
     * @param $listType
     * @param $externalIds
     * @return true
     */
    public static function executeTransferCustomer($inherit, $listType, $externalIds)
    {
        $config                 = SuiteCorpConfig::find()->where(['suite_id' => $inherit->suite_id, 'corp_id' => $inherit->corp_id])->limit(1)->one();
        $externalUseridArray100 = array_chunk($externalIds, 100);
        self::consoleLog($externalUseridArray100);
        self::consoleLog('分组数据 - 每次100个 :' . json_encode($externalUseridArray100, JSON_UNESCAPED_UNICODE));
        foreach ($externalUseridArray100 as $externalUseridList) {
            try {
                if ($listType == SuiteCorpInheritList::TYPE_1) { // 类型 1.客户
                    $resp = self::executeTransferCustomerByCurl($config, $listType, ['handover_userid' => $inherit->userid, 'takeover_userid' => $inherit->heir, 'external_userid' => $externalUseridList]);

                    self::consoleLog('executeTransferCustomerByCurl:' . json_encode($resp, JSON_UNESCAPED_UNICODE));

                    if (empty($resp) || empty($resp['customer'])) {
                        /**
                         * status 接替状态， 1-接替完毕 2-等待接替 3-客户拒绝 4-接替成员客户达到上限 9-失败
                         * 如果接口查询失败，那么本次所有交接可以改为失败状态
                         */
                        SuiteCorpInheritList::updateAll(
                            ['status' => SuiteCorpInheritList::STATUS_9, 'updated_at' => time(), 'errmsg' => '接口异常']
                            , ['and', ['inherit_id' => $inherit->id], ['type' => SuiteCorpInheritList::TYPE_1], ['in', 'external_id', $externalUseridList]]
                        );
                    } else {
                        $customerSuccess = []; // 成功分组
                        foreach ($resp['customer'] as $customerItem) {
                            if ($customerItem['errcode'] == 0) {
                                $customerSuccess[] = $customerItem['external_userid'];
                            } else {
                                SuiteCorpInheritList::updateAll(
                                    ['status' => SuiteCorpInheritList::STATUS_9, 'updated_at' => time(), 'errmsg' => '企业微信返回接口错误码：' . $customerItem['errcode']]
                                    , ['and', ['inherit_id' => $inherit->id], ['type' => SuiteCorpInheritList::TYPE_1], ['external_id' => $customerItem['external_userid']]]
                                );
                            }
                        }

                        if ($customerSuccess) { // TODO 成功后是等待接替状态，还需要通过api查询
                            SuiteCorpInheritList::updateAll(['status' => SuiteCorpInheritList::STATUS_2, 'updated_at' => time()], ['and', ['inherit_id' => $inherit->id], ['type' => SuiteCorpInheritList::TYPE_1], ['in', 'external_id', $customerSuccess]]);
                        }
                    }

                }

                if ($listType == SuiteCorpInheritList::TYPE_2) { // 类型 2.客户群
                    $resp = self::executeTransferGroupByCurl($config, $listType, ['handover_userid' => $inherit->userid, 'takeover_userid' => $inherit->heir, 'external_userid' => $externalUseridList]);
                    self::consoleLog('executeTransferGroupByCurl:' . json_encode($resp, JSON_UNESCAPED_UNICODE));


                    SuiteCorpInheritList::updateAll(
                        ['status' => SuiteCorpInheritList::STATUS_1, 'updated_at' => time(), 'takeover_time' => time()]
                        , ['and', ['inherit_id' => $inherit->id], ['type' => SuiteCorpInheritList::TYPE_2], ['in', 'external_id', $externalUseridList]]
                    );

                    // 重置失败群组
                    foreach ($resp['failed_chat_list'] as $itemFailed) {
                        SuiteCorpInheritList::updateAll(
                            ['status' => SuiteCorpInheritList::STATUS_9, 'updated_at' => time(), 'takeover_time' => 0, 'errmsg' => '企业微信返回接口错误：' . $itemFailed['errmsg']]
                            , ['and', ['inherit_id' => $inherit->id], ['type' => SuiteCorpInheritList::TYPE_2], ['external_id' => $itemFailed['chat_id']]]
                        );
                    }
                }
            } catch (\Exception $exception) {
                \Yii::warning('【UpdateAll Error】:' . $exception->getMessage());
                self::consoleLog('【UpdateAll Error】:' . $exception->getMessage());
            }
        }
        return true;
    }

    /**
     * 执行转移 - 客户 (向企微发送Curl请求)
     * @param $config
     * @param $type
     * @param $apiParams
     * @return mixed
     * @throws ErrException
     */
    public static function executeTransferCustomerByCurl($config, $type, $apiParams)
    {
        /* 类型 1.在职继承 2.离职继承 */
        switch ($type) {
            case SuiteCorpInherit::TYPE_1: // 在职继承
                $resp = SuiteService::externalContactTransferCustomer($config->suite_id, $config->corp_id, $apiParams);
            break;
            case SuiteCorpInherit::TYPE_2: // 离职继承
                $resp = SuiteService::externalContactResignedTransferCustomer($config->suite_id, $config->corp_id, $apiParams);
            break;
            default :
                throw new ErrException(Code::PARAMS_ERROR, '类型异常');
        }
        return $resp;
    }

    /**
     * 执行转移 - 客户群 (向企微发送Curl请求)
     * @param $config
     * @param $type
     * @param $apiParams
     * @return mixed
     * @throws ErrException
     */
    public static function executeTransferGroupByCurl($config, $type, $apiParams)
    {
        /* 类型 1.在职继承 2.离职继承 */
        switch ($type) {
            case SuiteCorpInherit::TYPE_1: // 在职继承
                $resp = SuiteService::externalContactGroupChatOnJobTransfer($config->suite_id, $config->corp_id, $apiParams);
            break;
            case SuiteCorpInherit::TYPE_2: // 离职继承
                $resp = SuiteService::externalContactGroupChatTransfer($config->suite_id, $config->corp_id, $apiParams);
            break;
            default :
                throw new ErrException(Code::PARAMS_ERROR, '类型异常');
        }
        return $resp;
    }

    /**
     * @param $config
     * @param $type
     * @param $apiParams
     * @return mixed
     * @throws ErrException
     */
    public static function executeTransferResultByCurl($config, $type, $apiParams)
    {
        /* 类型 1.在职继承 2.离职继承 */
        switch ($type) {
            case SuiteCorpInherit::TYPE_1: // 在职继承
                $resp = SuiteService::externalContactTransferResult($config->suite_id, $config->corp_id, $apiParams);
            break;
            case SuiteCorpInherit::TYPE_2: // 离职继承
                $resp = SuiteService::externalContactResignedTransferResult($config->suite_id, $config->corp_id, $apiParams);
            break;
            default :
                throw new ErrException(Code::PARAMS_ERROR, '类型异常');
        }
        return $resp;
    }
}