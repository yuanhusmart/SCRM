<?php

namespace console\controllers;


use common\models\SuiteCorpConfig;
use common\services\Service;
use common\services\SuiteCorpConfigAdminListService;
use common\services\SuiteCorpConfigChatAuthService;

class ConfigAdminListController extends BaseConsoleController
{

    /**
     * 企业应用管理员-权限变更处理
     *
     * php ./yii config-admin-list/storage
     *
     * Supervisor:aaw.config-admin-list.storage [ supervisorctl restart aaw.config-admin-list.storage: ]
     * Supervisor Log:/var/log/supervisor/aaw.config-admin-list.storage.log
     *
     * @return void
     */
    public function actionStorage()
    {
        Service::consoleConsumptionMQ(SuiteCorpConfigChatAuthService::MQ_FAN_OUT_EXCHANGE_CORP_CHANGE_AUTH, SuiteCorpConfigChatAuthService::MQ_CORP_ADMIN_LIST_QUEUE, function ($data) {
            \Yii::$app->db->close();
            self::consoleLog($data);
            try {
                SuiteCorpConfigAdminListService::eventChangeAuth(['SuiteId' => $data['suite_id'], 'AuthCorpId' => $data['corp_id']]);
            } catch (\Exception $e) {
                self::consoleLog('企业应用管理员-权限变更处理 广播 队列：' . json_encode($data, JSON_UNESCAPED_UNICODE) . ',跳过' . $e->getMessage());
            }
        }, 1, SuiteCorpConfigChatAuthService::MQ_FAN_OUT_ROUTING_KEY_CORP_CHANGE_AUTH, AMQP_EX_TYPE_FANOUT);
    }

    /**
     * 拉取应用管理员列表存储DB
     * 定时任务 每天凌晨0:40执行
     * php ./yii config-admin-list/pull
     * @return true
     */
    public function actionPull()
    {
        self::consoleLog(">> 开始}");
        try {
            $CorpConfigList = SuiteCorpConfig::find()->asArray()->all();
            self::consoleLog('获取服务商配置数据');
            foreach ($CorpConfigList as $item) {
                self::consoleLog('企业ID：' . $item['corp_id']);
                SuiteCorpConfigAdminListService::eventChangeAuth(['SuiteId' => $item['suite_id'], 'AuthCorpId' => $item['corp_id']]);
            }
        } catch (\Exception $e) {
            self::consoleLog($e->getMessage());
        }
        self::consoleLog(">> 全部完成}");
        return true;
    }

}