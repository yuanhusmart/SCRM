<?php

namespace console\controllers;


use common\errors\Code;
use common\errors\ErrException;
use common\models\OtsSuiteWorkWechatChatData;
use common\models\SuiteCorpExternalContact;
use common\models\SuiteCorpExternalContactFollowUser;
use common\sdk\TableStoreChain;
use common\services\Service;
use common\services\SuiteCorpExternalContactFollowUserService;
use common\services\SuiteCorpGroupChatService;

class ExternalContactSupplyController extends BaseConsoleController
{

    /**
     * 通过消息记录内的外部联系人ID-补充到DB
     * 定时任务 每天凌晨3:10执行
     * php ./yii external-contact-supply/timed-execution
     * @return void
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function actionTimedExecution()
    {
        self::consoleLog('按时间范围获取 消息记录内的 外部联系人ID：开始');

        $time = time();
        $sub  = 1 * 24 * 60 * 60; // 默认查询1天数据

        // 使用TableStoreChain链式调用
        $ots = new TableStoreChain(
            OtsSuiteWorkWechatChatData::TABLE_NAME,
            OtsSuiteWorkWechatChatData::TABLE_INDEX_NAME
        );

        // 设置查询条件
        $ots->whereBool(function ($query) {
            $query->whereBool(function ($subQuery) {
                $subQuery->whereExists('chatid');
            }, TableStoreChain::QUERY_TYPE_MUST_NOT);
        });

        // 设置时间范围
        $ots->whereRange('send_time', $time - $sub, $time, true, true);

        // 设置排序
        $ots->orderBy('send_time', TableStoreChain::SORT_ASC, TableStoreChain::SORT_MODE_AVG);

        // 设置分页和返回字段
        $ots->offsetLimit(0, 1000);
        $ots->select(['msgid', 'suite_id', 'corp_id', 'sender_id', 'sender_type', 'receiver_list']);

        $nextCursor = '';
        do {
            if (!empty($nextCursor)) {
                $ots->token($nextCursor);
            }
            // 执行查询 - 使用TableStoreChain的buildRequest方法
            $response = $ots->get();
            self::msgDataHandle($response);
            self::consoleLog('消息推送到：等待0s');
            $nextCursor = $response['next_token'] ?? '';
        } while ($nextCursor !== '');
        self::consoleLog('按时间范围获取 消息记录内的 外部联系人ID：结束');
    }

    /**
     * @param $response
     * @return void
     * @throws ErrException
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPExchangeException
     * @throws \AMQPQueueException
     */
    public static function msgDataHandle($response)
    {
        if (!empty($response['rows'])) {
            $rows       = $response['rows'];
            $routingKey = SuiteCorpGroupChatService::MQ_EXTERNAL_CONTACT_COMPENSATE_ROUTING_KEY;
            Service::pushRabbitMQMsg(SuiteCorpGroupChatService::MQ_EXTERNAL_CONTACT_COMPENSATE_EXCHANGE, SuiteCorpGroupChatService::MQ_EXTERNAL_CONTACT_COMPENSATE_QUEUE, function ($mq) use ($rows, $routingKey) {
                try {
                    foreach ($rows as $item) {
                        self::consoleLog($item);
                        $mq->publish(json_encode($item, JSON_UNESCAPED_UNICODE), $routingKey);
                    }
                } catch (\Exception $e) {
                    self::consoleLog($e->getMessage());
                }
            }, $routingKey);
        }
    }

    /**
     * 外部联系人补偿数据
     *
     * php ./yii external-contact-supply/external-contact-compensate
     * Supervisor:aaw.external-contact-supply.external-contact-compensate [ supervisorctl restart aaw.external-contact-supply.external-contact-compensate: ]
     * Supervisor Log:/var/log/supervisor/aaw.external-contact-supply.external-contact-compensate.log
     *
     * @return void
     */
    public function actionExternalContactCompensate()
    {
        Service::consoleConsumptionMQ(SuiteCorpGroupChatService::MQ_EXTERNAL_CONTACT_COMPENSATE_EXCHANGE, SuiteCorpGroupChatService::MQ_EXTERNAL_CONTACT_COMPENSATE_QUEUE, function ($msg) {
            \Yii::$app->db->close();
            self::consoleLog($msg);
            try {
                $msg['receiver_list'] = json_decode($msg['receiver_list'], true);

                /**
                 * 存在外部联系人 则 进行逻辑处理
                 * $externalUserid 外部联系人ID
                 * $userid 与外部联系人沟通的 内部用户ID
                 */
                if ($msg['sender_type'] == OtsSuiteWorkWechatChatData::USER_TYPE_2) {
                    $externalUserid = $msg['sender_id'];
                    $userid         = $msg['receiver_list'][0]['id'];

                } elseif ($msg['receiver_list'][0]['type'] == OtsSuiteWorkWechatChatData::USER_TYPE_2) {
                    $externalUserid = $msg['receiver_list'][0]['id'];
                    $userid         = $msg['sender_id'];
                }

                if (!empty($externalUserid) && !empty($userid)) {
                    $externalContactId = SuiteCorpExternalContact::find()
                                                                 ->where(['suite_id' => $msg['suite_id'], 'corp_id' => $msg['corp_id'], 'external_userid' => $externalUserid])
                                                                 ->select('id')
                                                                 ->scalar();

                    self::consoleLog('外部联系人主键ID：' . $externalContactId);

                    $transaction = \Yii::$app->db->beginTransaction();
                    try {
                        if (empty($externalContactId)) {
                            $externalContact = new SuiteCorpExternalContact();
                            $externalContact->load(['suite_id' => $msg['suite_id'], 'corp_id' => $msg['corp_id'], 'is_modify' => SuiteCorpExternalContact::IS_MODIFY_1, 'external_userid' => $externalUserid], '');
                            // 校验参数
                            if (!$externalContact->validate()) {
                                throw new ErrException(Code::PARAMS_ERROR, $externalContact->getError());
                            }
                            if (!$externalContact->save()) {
                                throw new ErrException(Code::CREATE_ERROR, $externalContact->getError());
                            }
                            $externalContactId = $externalContact->getPrimaryKey();
                            self::consoleLog('外部联系人2主键ID：' . $externalContactId);
                        }

                        $exists = SuiteCorpExternalContactFollowUser::find()->where(['external_contact_id' => $externalContactId, 'userid' => $userid])->exists();
                        if (!$exists) {
                            $externalContactFollowUserId = SuiteCorpExternalContactFollowUserService::create(['external_contact_id' => $externalContactId, 'userid' => $userid, 'remark' => '【系统自动补充】', 'createtime' => time()]);
                            self::consoleLog('外部联系人3主键ID：' . $externalContactId . ',附表主键ID：' . $externalContactFollowUserId);
                        }

                        $transaction->commit();
                    } catch (\Exception $e) {
                        $transaction->rollBack();
                        throw $e;
                    }
                }
            } catch (\Exception $e) {
                self::consoleLog('外部联系人补偿数据：' . json_encode($msg, JSON_UNESCAPED_UNICODE) . ',跳过' . $e->getMessage());
            }
        }, 1, SuiteCorpGroupChatService::MQ_EXTERNAL_CONTACT_COMPENSATE_ROUTING_KEY);
    }

}