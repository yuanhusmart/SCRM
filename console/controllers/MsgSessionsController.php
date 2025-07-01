<?php

namespace console\controllers;

use common\services\OtsSuiteWorkWechatChatDataService;
use common\services\Service;
use common\services\SuiteCorpSessionsService;

class MsgSessionsController extends BaseConsoleController
{

    /**
     * 企业微信 处理最近聊天会话 存储
     * php ./yii msg-sessions/storage
     *
     * Supervisor:aaw.msg-sessions.storage [ supervisorctl restart aaw.msg-sessions.storage: ]
     * Supervisor Log:/var/log/supervisor/aaw.msg-sessions.storage.log
     *
     * @return void
     */
    public function actionStorage()
    {
        Service::consoleConsumptionMQ(OtsSuiteWorkWechatChatDataService::MQ_FAN_OUT_EXCHANGE_CHAT_MSG, OtsSuiteWorkWechatChatDataService::MQ_FAN_OUT_QUEUE_CHAT_MSG_SESSIONS, function ($data) {
            \Yii::$app->db->close();
            self::consoleLog($data);
            try {
                SuiteCorpSessionsService::msgDataHandle($data);
            } catch (\Exception $e) {
                self::consoleLog('消息ID：' . $data['msgid'] . ',跳过' . $e->getMessage());
            }
        }, 1, OtsSuiteWorkWechatChatDataService::MQ_FAN_OUT_ROUTING_KEY_CHAT_MSG, AMQP_EX_TYPE_FANOUT);
    }

}