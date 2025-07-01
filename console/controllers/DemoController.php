<?php

namespace console\controllers;


use common\models\OtsSuiteWorkWechatChatData;
use common\models\SuiteCorpConfig;
use common\sdk\TableStoreChain;
use common\services\SuiteProgramService;

class DemoController extends BaseConsoleController
{

    /**
     * 专区调试代码 TODO
     * php ./yii demo/create-sentiment-task
     * @return false|void
     * @throws \common\errors\ErrException
     */
    public function actionCreateSentimentTask()
    {
        self::consoleLog('开始');
        $config = SuiteCorpConfig::findOne(4);
        self::consoleLog("数据与智能专区-企业:" . $config->corp_name);
    }

    /**
     * 获取情感分析结果 - 专区调试代码 TODO
     * php ./yii demo/get-sentiment-result
     * @param string $jobId
     * @return false|void
     * @throws \common\errors\ErrException
     */
    public function actionGetSentimentResult(string $jobId = '')
    {
        if (empty($jobId)) {
            self::consoleLog("任务ID无效");
            return false;
        }
        $config = SuiteCorpConfig::findOne(15);
        self::consoleLog("数据与智能专区-企业:" . $config->corp_name);

        // 获取企业授权给应用的知识集列表
        $responseData = SuiteProgramService::executionSyncCallProgram($config->suite_id, $config->corp_id, SuiteProgramService::PROGRAM_KNOWLEDGE_BASE_LIST, new \stdClass());
        self::consoleLog($responseData);
        die;

        // 获取知识集详情
        $responseData = SuiteProgramService::executionSyncCallProgram($config->suite_id, $config->corp_id, SuiteProgramService::PROGRAM_KNOWLEDGE_BASE_DETAIL, [
            "kb_id" => 1
        ]);
        self::consoleLog($responseData);
        self::consoleLog('结束');
        die;

        //$jobId = "ZkevGex05F9-s9tInU-Cv-9N1IjsG98erFI5m5It8nFiDTPyu0YqmzuGPjiezwBC";
        $responseData = SuiteProgramService::executionSyncCallProgram($config->suite_id, $config->corp_id, SuiteProgramService::PROGRAM_GET_SENTIMENT_RESULT, [
            'jobid' => $jobId
        ]);
        self::consoleLog($responseData);
        self::consoleLog('结束');

        // MSxOZjJZaG9qTmh6cDhINjRpUE9aZmg2RlN2ak5WVmU5dCxjYk9JNmpqbW9KNE5nQUZ5WWtIRDZ5WlEwNHhUbnpmUWEyZ2VsTlFZaFN2LHByb2doS2dGT3NjRXV3Qlkzcm5UU1RuOEh3QzhCTWMzaldKcQ==

    }

    /**
     * 创建分析任务 - 专区调试代码 TODO
     * php ./yii demo/create-analysis-job
     * @return void
     * @throws \common\errors\ErrException
     */
    public function actionCreateAnalysisJob()
    {
        // 创建TableStoreChain实例
        $tableStore = new TableStoreChain(
            OtsSuiteWorkWechatChatData::TABLE_NAME,
            OtsSuiteWorkWechatChatData::TABLE_INDEX_NAME
        );
        // 设置查询条件
        $tableStore->whereTerm('session_id', '68f5d89569d19e8851b3c64d50af6705');
        // 设置返回字段
        $tableStore->select(['msgid', 'msg_decrypted_secret_key']);
        $tableStore->offsetLimit(0, 1000);
        $tableStore->orderBy('send_time', TableStoreChain::SORT_ASC, TableStoreChain::SORT_MODE_AVG);
        // 执行查询
        $resp = $tableStore->get();
        // 处理结果
        $msgList = [];
        if (!empty($resp['rows'])) {
            foreach ($resp['rows'] as $row) {
                $msgList[] = [
                    'msgid'        => $row['msgid'],
                    'encrypt_info' => [
                        'secret_key' => $row['msg_decrypted_secret_key']
                    ],
                ];
            }
        }

        $config = SuiteCorpConfig::findOne(15);
        self::consoleLog("数据与智能专区-企业:" . $config->corp_name);
        $data = SuiteProgramService::asyncProgramTask($config['suite_id'], $config['corp_id'], SuiteProgramService::PROGRAM_CREATE_WW_MODEL_TASK, [
            "ability_id" => SuiteProgramService::PROGRAM_CONVERSATION_ANALYSIS,
            "msg_list"   => $msgList
        ]);

        self::consoleLog($data);
        die;
    }

    /**
     * 获取专区程序任务结果 - 专区调试代码 TODO
     * php ./yii demo/get-analysis-job-result
     * @param $jobId
     * @return void
     * @throws \common\errors\ErrException
     */
    public function actionGetAnalysisJobResult($jobId = '')
    {
        if (empty($jobId)) {
            $jobId = "Uc-AeUMjuyVeVhWqdxtsebOOonA-PdoJDO5uJtIM4o9IlxKky8yw8o6jxjrI9J7O";
        }
        $config = SuiteCorpConfig::findOne(15);
        self::consoleLog("数据与智能专区-企业:" . $config->corp_name);
        $data = SuiteProgramService::asyncProgramResult($config['suite_id'], $config['corp_id'], $jobId);
        self::consoleLog($data);
        die;
    }

    /**
     * 获取专区程序任务结果 - 专区调试代码 TODO
     * php ./yii demo/get-job-result
     * @param $jobId
     * @return void
     * @throws \common\errors\ErrException
     */
    public function actionGetJobResult($jobId = '')
    {
        if (empty($jobId)) {
            $jobId = "Uc-AeUMjuyVeVhWqdxtsebOOonA-PdoJDO5uJtIM4o9IlxKky8yw8o6jxjrI9J7O";
        }
        $config = SuiteCorpConfig::findOne(15);
        self::consoleLog("数据与智能专区-企业:" . $config->corp_name);
        $data = SuiteProgramService::executionSyncCallProgram($config['suite_id'], $config['corp_id'], SuiteProgramService::PROGRAM_GET_WW_MODEL_RESULT, ["jobid" => $jobId]);
        self::consoleLog($data);
        die;
    }
}