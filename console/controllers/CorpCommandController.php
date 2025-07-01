<?php

namespace console\controllers;


use common\models\SuiteCorpConfig;

class CorpCommandController extends BaseConsoleController
{

    /**
     * 企业配置脚本枚举
     * - 1.罗列所需通过企业ID执行的脚本
     * - 2.返回 commands 脚本命令、corps企业ID集合
     * php ./yii corp-command/enum
     *
     * shell 脚本执行日志：/var/log/crontab/ai-manage-script.log
     * @return false|string
     */
    public function actionEnum()
    {
        $commands = [
            /**
             * 参数说明：
             *    key   是所需执行的脚本路由
             *    value 是脚本所需启动的消费者数量
             * @uses \console\controllers\MomentController::actionDownloadResources()
             */


            // 朋友圈资源下载
            self::getClassActionRoute(MomentController::class, 'actionDownloadResources') => [
                'corps'   => false, // 不需要公司区分
                'numProc' => 1,     // 消费者数量
            ],

            // 企业微信 数据与智能专区 消息拉取 事件通知
            self::getClassActionRoute(ProgramPullMsgController::class, 'actionCorp')      => [
                'corps'   => true,  // 需要公司区分
                'numProc' => 1,     // 消费者数量
            ],

            // 企业微信 消息存储
            self::getClassActionRoute(MsgStorageController::class, 'actionToOts')         => [
                'corps'   => false,  // 不需要公司区分
                'numProc' => 2,      // 消费者数量
            ],

            // 企业微信 处理最近聊天会话 存储
            self::getClassActionRoute(MsgSessionsController::class, 'actionStorage')      => [
                'corps'   => false,  // 不需要公司区分
                'numProc' => 1,      // 消费者数量
            ],

            // 企业应用授权存档的成员-权限变更处理
            self::getClassActionRoute(ConfigChatAuthController::class, 'actionStorage')   => [
                'corps'   => false,  // 不需要公司区分
                'numProc' => 1,      // 消费者数量
            ],

            // 企业应用管理员-权限变更处理
            self::getClassActionRoute(ConfigAdminListController::class, 'actionStorage')  => [
                'corps'   => false,  // 不需要公司区分
                'numProc' => 1,      // 消费者数量
            ],

            // 企业微信 处理企业用户会话轨迹
            self::getClassActionRoute(MsgSessionsTraceController::class, 'actionStorage') => [
                'corps'   => false,  // 不需要公司区分
                'numProc' => 1,      // 消费者数量
            ],

            // 企业微信 处理内部群组数据 存储
            self::getClassActionRoute(MsgInternalGroupController::class, 'actionStorage') => [
                'corps'   => false,  // 不需要公司区分
                'numProc' => 1,      // 消费者数量
            ],

            // 企业微信 处理企业用户会话同意情况
            self::getClassActionRoute(MsgAgreeController::class, 'actionStorage')         => [
                'corps'   => false,  // 不需要公司区分
                'numProc' => 1,      // 消费者数量
            ],

            // 企业微信 数据与智能专区 命中关键词规则的会话记录拉取 事件通知
            self::getClassActionRoute(ProgramHitMsgController::class, 'actionPull')       => [
                'corps'   => false,  // 不需要公司区分
                'numProc' => 1,      // 消费者数量
            ],

            // 企业微信 数据与智能专区 命中关键词规则的会话记录拉取 数据存储
            self::getClassActionRoute(ProgramHitMsgController::class, 'actionToSave')     => [
                'corps'   => false,  // 不需要公司区分
                'numProc' => 1,      // 消费者数量
            ],

            // 企业微信 数据与智能专区 AI 会话分析前检测 并创建分析任务
            self::getClassActionRoute(ChatAnalysisTaskController::class, 'actionBeforeCheck')     => [
                'corps'   => false,  // 不需要公司区分
                'numProc' => 1,      // 消费者数量
            ],

            // 企业微信 数据与智能专区 AI会话分析 每日会话分析记录存储
            self::getClassActionRoute(ChatAnalysisTaskController::class, 'actionDateStorage')     => [
                'corps'   => false,  // 不需要公司区分
                'numProc' => 1,      // 消费者数量
            ],
            self::getClassActionRoute(BusinessOpportunitiesAnalysisController::class, 'actionCheckSchedule')     => [
                /**
                 * 商机跟进助手AI分析 配置检查
                 * @uses \console\controllers\BusinessOpportunitiesAnalysisController::actionCheckSchedule()
                 */
                'corps'   => false,
                'numProc' => 1,
            ],
            self::getClassActionRoute(BusinessOpportunitiesAnalysisController::class, 'actionMain')     => [
                /**
                 * 商机跟进助手AI分析 分析执行
                 * @uses \console\controllers\BusinessOpportunitiesAnalysisController::actionMain()
                 */
                'corps'   => true,
                'numProc' => 1,
            ],
        ];

        // 获取可执行的企业ID合集
        $corps = SuiteCorpConfig::find()->select('id')->where(['status' => SuiteCorpConfig::STATUS_1])->asArray()->column();

        $data = [];
        foreach ($commands as $action => $consumer) {
            if ($consumer['corps']) {
                foreach ($corps as $id) {
                    $data[] = [
                        'commands'   => sprintf('%s %d', $action, $id),
                        'consumer'   => $consumer['numProc'],
                        'supervisor' => sprintf('%s%s.%d', \Yii::$app->params["redisPrefix"], str_replace('/', '.', $action), $id),
                    ];
                }
            } else {
                $data[] = [
                    'commands'   => $action,
                    'consumer'   => $consumer['numProc'],
                    'supervisor' => sprintf('%s%s', \Yii::$app->params["redisPrefix"], str_replace('/', '.', $action)),
                ];
            }
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die;
    }
}