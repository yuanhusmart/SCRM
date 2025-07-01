<?php

namespace console\controllers;

use common\models\SuiteCorpSessionsTrace;
use common\services\OtsSuiteWorkWechatChatDataService;
use common\services\Service;
use common\services\SuiteCorpSessionsTraceService;

class MsgSessionsTraceController extends BaseConsoleController
{

    /**
     * 企业微信 处理企业用户会话轨迹
     * php ./yii msg-sessions-trace/storage
     *
     * Supervisor:aaw.msg-sessions-trace.storage [ supervisorctl restart aaw.msg-sessions-trace.storage: ]
     * Supervisor Log:/var/log/supervisor/aaw.msg-sessions-trace.storage.log
     *
     * @return void
     */
    public function actionStorage()
    {
        Service::consoleConsumptionMQ(OtsSuiteWorkWechatChatDataService::MQ_FAN_OUT_EXCHANGE_CHAT_MSG, OtsSuiteWorkWechatChatDataService::MQ_FAN_OUT_QUEUE_CHAT_MSG_SESSIONS_TRACE, function ($data) {
            \Yii::$app->db->close();
            self::consoleLog($data);
            try {
                SuiteCorpSessionsTraceService::msgDataHandle($data);
            } catch (\Exception $e) {
                self::consoleLog('消息ID：' . $data['msgid'] . ',跳过' . $e->getMessage());
            }
        }, 1, OtsSuiteWorkWechatChatDataService::MQ_FAN_OUT_ROUTING_KEY_CHAT_MSG, AMQP_EX_TYPE_FANOUT);
    }

}