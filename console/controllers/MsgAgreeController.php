<?php

namespace console\controllers;

use common\models\OtsSuiteWorkWechatChatData;
use common\services\OtsSuiteWorkWechatChatDataService;
use common\services\Service;
use common\services\SuiteCorpChatAgreeService;

class MsgAgreeController extends BaseConsoleController
{

    /**
     * 企业微信 处理企业用户会话同意情况
     * php ./yii msg-agree/storage
     *
     * Supervisor:aaw.msg-agree.storage [ supervisorctl restart aaw.msg-agree.storage: ]
     * Supervisor Log:/var/log/supervisor/aaw.msg-agree.storage.log
     *
     * @return void
     */
    public function actionStorage()
    {
        Service::consoleConsumptionMQ(OtsSuiteWorkWechatChatDataService::MQ_FAN_OUT_EXCHANGE_CHAT_MSG, OtsSuiteWorkWechatChatDataService::MQ_FAN_OUT_QUEUE_CHAT_MSG_AGREE, function ($data) {
            \Yii::$app->db->close();
            self::consoleLog($data);
            try {
                switch ($data['msgtype']) {
                    case OtsSuiteWorkWechatChatData::MSG_TYPE_24:
                    case OtsSuiteWorkWechatChatData::MSG_TYPE_25:
                        self::consoleLog(OtsSuiteWorkWechatChatData::MSG_TYPE[$data['msgtype']]);
                        $data['sender_id']   = $data['sender']['id'];
                        $data['sender_type'] = $data['sender']['type'];
                        SuiteCorpChatAgreeService::create($data);
                    break;
                }
            } catch (\Exception $e) {
                self::consoleLog('消息ID：' . $data['msgid'] . ',跳过' . $e->getMessage());
            }
        }, 1, OtsSuiteWorkWechatChatDataService::MQ_FAN_OUT_ROUTING_KEY_CHAT_MSG, AMQP_EX_TYPE_FANOUT);
    }

}