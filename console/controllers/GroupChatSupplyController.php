<?php

namespace console\controllers;


use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpGroupChat;
use common\models\SuiteCorpGroupChatMember;
use common\services\OtsSuiteWorkWechatChatDataService;
use common\services\Service;
use common\services\SuiteCorpGroupChatMemberService;
use common\services\SuiteCorpGroupChatService;

class GroupChatSupplyController extends BaseConsoleController
{

    /**
     * 通过消息记录内的群组ID-补充到DB
     * 定时任务 每天凌晨0:20执行
     * php ./yii group-chat-supply/timed-execution
     * @return void
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function actionTimedExecution()
    {
        self::consoleLog('按时间范围获取 消息记录内的 群组ID：开始');

        self::consoleLog('按时间范围获取 消息记录内的 群组ID：结束');
    }

    /**
     * @param $response
     * @return void
     */
    public static function msgDataHandle($response)
    {
        if (!empty($response['rows'])) {
            $rows       = $response['rows'];
            $routingKey = SuiteCorpGroupChatService::MQ_GROUP_COMPENSATE_ROUTING_KEY;
            Service::pushRabbitMQMsg(SuiteCorpGroupChatService::MQ_GROUP_COMPENSATE_EXCHANGE, SuiteCorpGroupChatService::MQ_GROUP_COMPENSATE_QUEUE, function ($mq) use ($rows, $routingKey) {
                try {
                    foreach ($rows as $item) {
                        $row = [];
                        foreach ($item['primary_key'] as $pkValue) {
                            $row[$pkValue[0]] = $pkValue[1];
                        }
                        foreach ($item['attribute_columns'] as $columnsValue) {
                            $row[$columnsValue[0]] = $columnsValue[1];
                        }
                        self::consoleLog($row);
                        $mq->publish(json_encode($row, JSON_UNESCAPED_UNICODE), $routingKey);
                    }
                } catch (\Exception $e) {
                    self::consoleLog($e->getMessage());
                }
            }, $routingKey);
        }
    }

    /**
     *  群组补偿数据
     *  php ./yii group-chat-supply/group-compensate
     *  Supervisor:aaw.group-chat-supply.group-compensate [ supervisorctl restart aaw.group-chat-supply.group-compensate: ]
     *  Supervisor Log:/var/log/supervisor/aaw.group-chat-supply.group-compensate.log
     * @return void
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function actionGroupCompensate()
    {
        $routingKey = SuiteCorpGroupChatService::MQ_GROUP_COMPENSATE_ROUTING_KEY;
        Service::consoleConsumptionMQ(SuiteCorpGroupChatService::MQ_GROUP_COMPENSATE_EXCHANGE, SuiteCorpGroupChatService::MQ_GROUP_COMPENSATE_QUEUE, function ($msg) {
            \Yii::$app->db->close();
            self::consoleLog($msg);
            try {
                $groupChatExists = SuiteCorpGroupChat::find()->where(['chat_id' => $msg['chatid']])->exists();
                if ($groupChatExists) {
                    throw new ErrException(Code::PARAMS_ERROR, '群组已存在无需处理');
                }
                $response                      = OtsSuiteWorkWechatChatDataService::getMsgById(['msgid' => $msg['msgid'], 'return_names' => ['receiver_list', 'sender_id', 'sender_type']]);
                $groupChatCreate['suite_id']   = $msg['suite_id'];
                $groupChatCreate['corp_id']    = $msg['corp_id'];
                $groupChatCreate['chat_id']    = $msg['chatid'];
                $groupChatCreate['notice']     = '【系统补充群组】';
                $groupChatCreate['group_type'] = SuiteCorpGroupChat::GROUP_TYPE_2;
                $groupChatCreate['is_modify']  = SuiteCorpGroupChat::IS_MODIFY_1;
                if (!empty($response[0])) {
                    $memberList   = json_decode($response[0]['receiver_list'], true);
                    $memberList[] = ['type' => $response[0]['sender_type'], 'id' => $response[0]['sender_id']];
                    foreach ($memberList as &$value) {
                        $value['userid'] = $value['id'];
                        // 如果有一个外部联系人 群组类型变更为 外部群组
                        if ($value['type'] == SuiteCorpGroupChatMember::GROUP_CHAT_TYPE_2) {
                            $groupChatCreate['group_type'] = SuiteCorpGroupChat::GROUP_TYPE_1;
                        }
                    }
                }
                $groupChatCreate['member_count'] = count($memberList);
                self::consoleLog('群组ID：' . $groupChatCreate['chat_id'] . ',准备写入');
                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    $groupChat = new SuiteCorpGroupChat();
                    $groupChat->load($groupChatCreate, '');
                    // 校验参数
                    if (!$groupChat->validate()) {
                        throw new ErrException(Code::PARAMS_ERROR, $groupChat->getError());
                    }
                    if (!$groupChat->save()) {
                        throw new ErrException(Code::CREATE_ERROR, $groupChat->getError());
                    }
                    $groupChatId = $groupChat->getPrimaryKey();
                    SuiteCorpGroupChatMemberService::batchInsertGroupChatMember($groupChatId, $memberList);
                    $transaction->commit();
                    self::consoleLog('群组ID准备写入完毕，群组ID：' . $groupChatCreate['chat_id'] . '，主键ID：' . $groupChatId);
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    throw $e;
                }
            } catch (\Exception $e) {
                self::consoleLog('群组补偿数据：' . json_encode($msg, JSON_UNESCAPED_UNICODE) . ',跳过' . $e->getMessage());
            }
        }, 1, $routingKey);
    }

    /**
     * 通过重复群组ID-补全群组名称
     * 定时任务 每天凌晨1:00执行
     * php ./yii group-chat-supply/timed-name
     * @return void
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function actionTimedName()
    {
        self::consoleLog('通过重复群组ID-补全群组名称：开始');
        $batchId = 1;
        // 每次处理100条记录
        $batchSize = 100;
        foreach (SuiteCorpGroupChat::find()
                                   ->select('chat_id')
                                   ->where(['<>', 'chat_id', ''])
                                   ->groupBy('chat_id')
                                   ->having(['>', 'COUNT(chat_id)', 1])
                                   ->asArray()
                                   ->batch($batchSize) as $groupChat) {
            $batch = 1;

            self::consoleLog("执行批次ID： $batchId ");

            foreach ($groupChat as $item) {
                self::consoleLog("执行批次ID： $batchId , 批次序号：$batch");
                $name = SuiteCorpGroupChat::find()
                                          ->select('name')
                                          ->andWhere(['chat_id' => $item['chat_id']])
                                          ->andWhere(['NOT IN', 'name', ['', '非企业客户群', '未命名内部群']])
                                          ->limit(1)
                                          ->scalar();
                self::consoleLog("需要补全的群组ID： " . $item['chat_id'] . " , 名称：$name");

                // 有名称则进行更新数据
                if (!empty($name)) {
                    $ids = SuiteCorpGroupChat::find()
                                             ->select('id,chat_id,name')
                                             ->andWhere(['chat_id' => $item['chat_id']])
                                             ->andWhere(['IN', 'name', ['', '非企业客户群', '未命名内部群']])
                                             ->asArray()
                                             ->all();

                    self::consoleLog('等待更新数据：' . json_encode($ids, JSON_UNESCAPED_UNICODE));
                    if ($ids) {
                        $ids = array_column($ids, 'id');
                        self::consoleLog('更新ID集合：' . json_encode($ids, JSON_UNESCAPED_UNICODE));
                        SuiteCorpGroupChat::updateAll(['name' => $name], ['IN', 'id', $ids]);
                    }
                }
                $batch++;
            }
            $batchId++;
        }
        self::consoleLog('通过重复群组ID-补全群组名称：结束');
    }

}