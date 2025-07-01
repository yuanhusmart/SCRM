<?php

namespace console\controllers;


use common\models\SuiteCorpConfig;
use common\models\SuiteCorpConfigChatAuth;
use common\services\Service;
use common\services\SuiteCorpConfigChatAuthService;
use common\services\SuiteService;

class ConfigChatAuthController extends BaseConsoleController
{

    /**
     * 企业应用授权存档的成员-权限变更处理
     *
     * php ./yii config-chat-auth/storage
     *
     * Supervisor:aaw.config-chat-auth.storage [ supervisorctl restart aaw.config-chat-auth.storage: ]
     * Supervisor Log:/var/log/supervisor/aaw.config-chat-auth.storage.log
     *
     * @return void
     */
    public function actionStorage()
    {
        Service::consoleConsumptionMQ(SuiteCorpConfigChatAuthService::MQ_FAN_OUT_EXCHANGE_CORP_CHANGE_AUTH, SuiteCorpConfigChatAuthService::MQ_CORP_CHANGE_AUTH_QUEUE, function ($data) {
            \Yii::$app->db->close();

            sleep(1);
            self::consoleLog('延迟1s处理消息：企业微信拉取存档人员接口存在延迟');
            self::consoleLog($data);
            try {

                $transaction = \Yii::$app->db->beginTransaction();
                try {
                    SuiteCorpConfigChatAuth::updateAll(['deleted_at' => time()], ['and', ['config_id' => $data['id']]]);
                    $transaction->commit();
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    throw $e;
                }
                $params     = ['limit' => 1000];
                $nextCursor = '';
                do {
                    if (!empty($nextCursor)) {
                        $params['cursor'] = $nextCursor;
                    }
                    // 获取授权存档的成员列表
                    $authUserList = SuiteService::getAuthUserList($data['suite_id'], $data['corp_id'], $params);
                    $transaction  = \Yii::$app->db->beginTransaction();
                    try {
                        // 处理数据
                        foreach ($authUserList['auth_user_list'] as $authUser) {
                            foreach ($authUser['edition_list'] as $edition) {
                                SuiteCorpConfigChatAuthService::create(['config_id' => $data['id'], 'suite_id' => $data['suite_id'], 'corp_id' => $data['corp_id'], 'userid' => $authUser['userid'], 'edition' => $edition, 'deleted_at' => 0]);
                            }
                        }
                        $transaction->commit();
                    } catch (\Exception $e) {
                        $transaction->rollBack();
                        throw $e;
                    }
                    self::consoleLog('消息推送到：等待0s');
                    $nextCursor = $authUserList['next_cursor'] ?? '';
                } while ($nextCursor !== '');
            } catch (\Exception $e) {
                self::consoleLog('应用授权存档的成员-权限变更 广播 队列：' . json_encode($data, JSON_UNESCAPED_UNICODE) . ',跳过' . $e->getMessage());
            }
        }, 1, SuiteCorpConfigChatAuthService::MQ_FAN_OUT_ROUTING_KEY_CORP_CHANGE_AUTH, AMQP_EX_TYPE_FANOUT);
    }

    /**
     * 企业应用授权存档的成员-数据拉取 (每天凌晨3点执行,定时跑全量)
     * 定时任务 php ./yii config-chat-auth/pull 1
     * @param int $corpId 企业主键ID 不传入默认拉取所有企业
     * @return true
     */
    public function actionPull(int $corpId = 0)
    {
        self::consoleLog(">> 开始}");
        $corpQuery = SuiteCorpConfig::find()->select('id,suite_id,corp_id');
        if ($corpId) {
            $corpQuery->where(['id' => $corpId]);
        }
        $suiteList = $corpQuery->asArray()->all();
        try {
            self::consoleLog('获取服务商配置数据');
            self::consoleLog($suiteList);
            foreach ($suiteList as $data) {
                $data['TimeStamp'] = time();
                $data['InfoType']  = 'scheduled_tasks_change_auth';
                $routingKey        = SuiteCorpConfigChatAuthService::MQ_FAN_OUT_ROUTING_KEY_CORP_CHANGE_AUTH;
                // 批量推送到MQ  投递广播消息
                Service::pushRabbitMQMsg(SuiteCorpConfigChatAuthService::MQ_FAN_OUT_EXCHANGE_CORP_CHANGE_AUTH, '', function ($mq) use ($data, $routingKey) {
                    try {
                        $mq->publish(json_encode($data, JSON_UNESCAPED_UNICODE), $routingKey);
                    } catch (\Exception $e) {
                        \Yii::warning($e->getMessage());
                    }
                }, $routingKey, AMQP_EX_TYPE_FANOUT);
            }
        } catch (\Exception $e) {
            self::consoleLog($e->getMessage());
        }
        self::consoleLog(">> 全部完成}");
        return true;
    }

}