<?php

namespace console\controllers;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpConfig;
use common\models\SuiteCorpGroupChat;
use common\services\OtsSuiteWorkWechatChatDataService;
use common\services\Service;
use common\services\SuiteCorpConfigService;
use common\services\SuiteCorpGroupChatService;
use common\services\SuiteProgramService;
use common\services\SuiteService;

class MsgInternalGroupController extends BaseConsoleController
{

    /**
     * 企业微信 处理内部群组数据 存储
     * php ./yii msg-internal-group/storage
     *
     * Supervisor:aaw.msg-internal-group.storage [ supervisorctl restart aaw.msg-internal-group.storage: ]
     * Supervisor Log:/var/log/supervisor/aaw.msg-internal-group.storage.log
     *
     * @return void
     */
    public function actionStorage()
    {
        Service::consoleConsumptionMQ(OtsSuiteWorkWechatChatDataService::MQ_FAN_OUT_EXCHANGE_CHAT_MSG, OtsSuiteWorkWechatChatDataService::MQ_FAN_OUT_QUEUE_CHAT_INTERNAL_GROUP_SESSIONS, function ($data) {
            \Yii::$app->db->close();
            self::consoleLog($data);
            try {
                if (empty($data['chatid'])) {
                    throw new ErrException(Code::PARAMS_ERROR, '单聊,无需处理');
                }
                // 查询库内是否存在
                $groupChat = SuiteCorpGroupChat::findOne(['suite_id' => $data['suite_id'], 'corp_id' => $data['corp_id'], 'chat_id' => $data['chatid']]);
                if (!empty($groupChat) && $groupChat->group_type == SuiteCorpGroupChat::GROUP_TYPE_1) {
                    throw new ErrException(Code::PARAMS_ERROR, '外部群,无需处理');
                }
                if (!empty($groupChat) && $data['send_time'] <= $groupChat->updated_at) {
                    throw new ErrException(Code::PARAMS_ERROR, '消息发送时间 <= 更新时间,无需处理');
                }
                // 通过企微API查询内部群信息
                $chat = SuiteProgramService::executionSyncCallProgram($data['suite_id'], $data['corp_id'], SuiteProgramService::PROGRAM_ABILITY_GET_GROUP_CHAT, ['chatid' => $data['chatid']]);

                if (!empty($chat['room_create_time'])) {
                    $chat['chatid']    = $data['chatid'];
                    $chat['suite_id']  = $data['suite_id'];
                    $chat['corp_id']   = $data['corp_id'];
                    $chat['send_time'] = $data['send_time'];
                    $groupChatId       = SuiteCorpGroupChatService::createInternalGroup($chat);
                    self::consoleLog('消息ID：' . $data['msgid'] . ',群组ID：' . $groupChatId);
                }
            } catch (\Exception $e) {
                self::consoleLog('消息ID：' . $data['msgid'] . ',跳过' . $e->getMessage());
            }
        }, 1, OtsSuiteWorkWechatChatDataService::MQ_FAN_OUT_ROUTING_KEY_CHAT_MSG, AMQP_EX_TYPE_FANOUT);
    }

}