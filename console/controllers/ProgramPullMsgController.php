<?php

namespace console\controllers;

use common\models\SuiteCorpConfig;
use common\services\OtsSuiteWorkWechatChatDataService;
use common\services\Service;
use common\services\SuiteCorpProgramNextCursorService;
use common\services\SuiteProgramService;

class ProgramPullMsgController extends BaseConsoleController
{

    /**
     * 企业微信 数据与智能专区 消息拉取 事件通知
     * php ./yii program-pull-msg/event-notice
     *
     * Supervisor:aaw.program-pull-msg.event-notice [ supervisorctl restart aaw.program-pull-msg.event-notice ]
     * Supervisor Log:/var/log/supervisor/aaw.program-pull-msg.event-notice.log
     * 「 sudo supervisorctl status all | grep work-wechat-api | awk '{print $1}' | xargs sudo supervisorctl stop 」
     *
     *
     *
     * php ./yii program-pull-msg/corp 1～15
     *
     * Supervisor:aaw.program-pull-msg.corp1 [ supervisorctl restart aaw.program-pull-msg.corp1 ]
     * Supervisor Log:/var/log/supervisor/aaw.program-pull-msg.corp1.log
     * 「 sudo supervisorctl status all | grep aaw.program-pull-msg.corp | awk '{print $1}' | xargs sudo supervisorctl stop 」
     * @param int $id
     * @return false|void
     */
    public function actionCorp(int $id = 0)
    {
        if (!$id) {
            self::consoleLog(">> 请输入企业内部ID}");
            return false;
        }
        $AuthCorpId = SuiteCorpConfig::find()->select('corp_id')->where(['id' => $id])->limit(1)->scalar();
        self::consoleLog("数据与智能专区-企业ID:" . $AuthCorpId);
        self::consumeQueue($id,
            SuiteProgramService::PROGRAM_MSG_NOTICE_MQ_EXCHANGE . $AuthCorpId,
            SuiteProgramService::PROGRAM_MSG_NOTICE_MQ_QUEUE . $AuthCorpId,
            SuiteProgramService::PROGRAM_MSG_NOTICE_MQ_ROUTING_KEY . $AuthCorpId);
    }

    /**
     * @param $action
     * @param $exchange
     * @param $queue
     * @param $routingKey
     * @return void
     */
    public static function consumeQueue($action, $exchange, $queue, $routingKey)
    {
        self::consoleLog('数据与智能专区-消息拉取方法：' . $action);
        Service::consoleConsumptionMQ($exchange, $queue, function ($data) use ($exchange, $queue, $routingKey, $action) {
            \Yii::$app->db->close();
            self::consoleLog($data);
            try {
                $msg = self::pullMsgByMaxSeqAndCorpId($data);
                // 是否还有更多数据。0-否；1-是:消息重新入队
                if ($msg) {
                    Service::pushRabbitMQMsg($exchange, $queue, function ($mq) use ($data, $routingKey) {
                        try {
                            $mq->publish(json_encode($data, JSON_UNESCAPED_UNICODE), $routingKey);
                        } catch (\Exception $e) {
                            self::consoleLog($e->getMessage());
                        }
                    }, $routingKey);
                }
            } catch (\Exception $e) {
                self::consoleLog('数据与智能专区-消息拉取事件通知：' . json_encode($data, JSON_UNESCAPED_UNICODE) . ',跳过' . $e->getMessage());
            }
        }, 1, $routingKey);
    }

    /**
     * 根据 nextCursor 拉取企业消息并入队,出参是否继续拉取
     * @param $params
     * @return bool
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPExchangeException
     * @throws \AMQPQueueException
     * @throws \common\errors\ErrException
     */
    public static function pullMsgByMaxSeqAndCorpId($params)
    {
        $suiteId   = $params['config']['suite_id'] ?? '';
        $corpId    = $params['config']['corp_id'] ?? '';
        $token     = $params['conversation_new_message']['token'] ?? '';
        $timeStamp = $params['timestamp'] ?? 0; // token 有效时长 10分钟，需要根据timestamp判断token是否有效，如果失效消息作废
        if (!$suiteId || !$corpId || !$token) {
            return false;
        }
        $diff = time() - $timeStamp;
        // 判断当前时间跟消息通知时间是否相差大于10分钟
        if ($diff >= 600) {
            return false;
        }
        $nextCursor = SuiteCorpProgramNextCursorService::getNextCursorByCorpId($suiteId, $corpId);
        if ($nextCursor) {
            $paramsSyncMsg['cursor'] = $nextCursor;
        }
        $paramsSyncMsg['token'] = $token;
        $paramsSyncMsg['limit'] = 100;
        self::consoleLog($paramsSyncMsg);

        // 一次性拉取 100 条数据, TODO 暂时放弃token入参，token拉取有时间限制 会导致拉取不到数据情况
        $data = self::syncMsg($suiteId, $corpId, $paramsSyncMsg);

        self::consoleLog('数据与智能专区一次性拉取' . $paramsSyncMsg['limit'] . '条数据');
        self::consoleLog($data);

        if ($data) {
            $routingKey = OtsSuiteWorkWechatChatDataService::MQ_FAN_OUT_ROUTING_KEY_CHAT_MSG;
            // 批量推送到MQ  投递广播消息
            Service::pushRabbitMQMsg(OtsSuiteWorkWechatChatDataService::MQ_FAN_OUT_EXCHANGE_CHAT_MSG, '', function ($mq) use ($data, $routingKey, $suiteId, $corpId) {
                try {
                    foreach ($data['msg_list'] as $item) {
                        $item['suite_id'] = $suiteId;
                        $item['corp_id']  = $corpId;
                        if (!empty($item['chatid'])) {
                            // 如果是群聊 会话ID = 群组ID
                            $item['session_id'] = $item['chatid'];
                        } else {
                            // 如果是单聊 会话ID =（发送人、接收人 字典升序md5）
                            $item['session_id'] = dictSortMd5([$item['sender']['id'], $item['receiver_list'][0]['id']]);
                        }
                        if (!empty($item['send_time'])) {
                            $item['send_date'] = date('Y-m-d', $item['send_time']);
                        }
                        $mq->publish(json_encode($item, JSON_UNESCAPED_UNICODE), $routingKey);
                    }
                } catch (\Exception $e) {
                    self::consoleLog($e->getMessage());
                    \Yii::warning($e->getMessage());
                }
            }, $routingKey, AMQP_EX_TYPE_FANOUT);
            if (!empty($data['next_cursor']) && !empty($data['msg_list'])) {
                self::consoleLog('数据与智能专区-设置NextCursor游标');
                SuiteCorpProgramNextCursorService::setNextCursorByCorpId($suiteId, $corpId, $data['next_cursor']);
            }
        }
        // 是否还有更多数据。0-否；1-是。
        if (!empty($data['has_more'])) {
            return true;
        }
        return false;
    }

    /**
     * @param $suiteId
     * @param $corpId
     * @param $params
     * @return array|mixed
     * @throws \common\errors\ErrException
     */
    public static function syncMsg($suiteId, $corpId, $params = [])
    {
        $input = [
            "program_id"   => \Yii::$app->params["workWechat"]['programId'],
            "ability_id"   => SuiteProgramService::PROGRAM_ABILITY_SYNC_MSG,
            "request_data" => json_encode([
                "input" => [
                    "func"     => "sync_msg",
                    "func_req" => $params
                ]
            ], JSON_UNESCAPED_UNICODE)
        ];
        self::consoleLog('获取会话记-入参:' . json_encode($input, JSON_UNESCAPED_UNICODE));
        $return = SuiteProgramService::syncCallProgram($suiteId, $corpId, $input);
        self::consoleLog('获取会话记-出参:' . json_encode($return, JSON_UNESCAPED_UNICODE));
        return $return;
    }

}