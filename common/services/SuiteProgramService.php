<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpConfig;
use common\services\workWechat\ErrCode;

/**
 * 服务商 数据与智能专区
 */
class SuiteProgramService extends Service
{

    // 数据与智能专区 消息拉取 队列信息(隔离主体)
    const PROGRAM_MSG_NOTICE_MQ_EXCHANGE    = 'program.msg.ex.';
    const PROGRAM_MSG_NOTICE_MQ_QUEUE       = 'aaw.program.msg.que.';
    const PROGRAM_MSG_NOTICE_MQ_ROUTING_KEY = 'program.msg.rk.';


    // 数据与智能专区 获取命中关键词规则的会话记录 队列信息
    const PROGRAM_HIT_KEYWORD_NOTICE_MQ_EXCHANGE    = 'program.hit.keyword.ex';
    const PROGRAM_HIT_KEYWORD_NOTICE_MQ_QUEUE       = 'aaw.program.hit.keyword.que';
    const PROGRAM_HIT_KEYWORD_NOTICE_MQ_ROUTING_KEY = 'program.hit.keyword.rk';

    const PROGRAM_MSG_TYPE_EVENT = 'event';

    const PROGRAM_EVENT_PROGRAM_NOTIFY = 'program_notify';

    const EVENT_CHANGE_APP_ADMIN = 'change_app_admin'; // 应用管理员变更通知

    // 能力ID 专区通知应用
    const PROGRAM_ABILITY_SPEC_NOTIFY_APP = 'input_spec_notify_app';
    // 能力ID 添加获取会话记录能力
    const PROGRAM_ABILITY_SYNC_MSG = 'input_sync_msg';
    // 能力ID 获取回调数据能力
    const PROGRAM_ABILITY_DO_ASYNC_JOB = 'input_do_async_job';
    // 能力ID 获取内部群信息
    const PROGRAM_ABILITY_GET_GROUP_CHAT = 'input_get_group_chat';
    // 能力ID 会话名称搜索
    const PROGRAM_ABILITY_SEARCH_CHAT = 'input_search_chat';
    // 能力ID 会话消息搜索
    const PROGRAM_ABILITY_SEARCH_MSG = 'input_search_msg';
    // 能力ID 关键词-新增关键词规则
    const PROGRAM_ABILITY_CREATE_RULE = 'input_create_rule';
    // 能力ID 关键词-获取关键词列表
    const PROGRAM_ABILITY_GET_RULE_LIST = 'input_get_rule_list';
    // 能力ID 关键词-获取关键词规则详情
    const PROGRAM_ABILITY_GET_RULE_DETAIL = 'input_get_rule_detail';
    // 能力ID 关键词-修改关键词规则
    const PROGRAM_ABILITY_UPDATE_RULE = 'input_update_rule';
    // 能力ID 关键词-删除关键词规则
    const PROGRAM_ABILITY_DELETE_RULE = 'input_delete_rule';
    // 能力ID 获取命中关键词规则的会话记录
    const PROGRAM_ABILITY_GET_HIT_MSG_LIST = 'input_get_hit_msg_list';
    // 能力ID 创建会话内容导出任务
    const PROGRAM_CREATE_CHAT_DATA_EXPORT_JOB = 'input_create_chatdata_export_job';
    // 能力ID 获取会话内容导出任务结果
    const PROGRAM_GET_CHAT_DATA_EXPORT_JOB_STATUS = 'input_get_chatdata_export_job_status';
    // 能力ID 创建自有分析程序任务
    const PROGRAM_CREATE_PROGRAM_TASK = 'input_create_program_task';
    // 能力ID 获取自有分析程序结果
    const PROGRAM_GET_PROGRAM_TASK_RESULT = 'input_get_program_task_result';
    // 能力ID 上报异步任务结果
    const PROGRAM_ASYNC_JOB_CALL_BACK = 'input_program_async_job_call_back';
    // 能力ID 创建自定义模型任务
    const PROGRAM_CREATE_MODEL_TASK = 'input_create_model_task';
    // 能力ID 获取自定义模型结果
    const PROGRAM_GET_MODEL_TASK_RESULT = 'input_get_model_task_result';
    // 能力ID 创建企微通用模型任务
    const PROGRAM_CREATE_WW_MODEL_TASK = 'input_create_ww_model_task';
    // 能力ID 获取企微通用模型结果
    const PROGRAM_GET_WW_MODEL_RESULT = 'input_get_ww_model_result';
    // 能力ID 创建话术推荐任务
    const PROGRAM_CREATE_RECOMMEND_DIALOG_TASK = 'input_create_recommend_dialog_task';
    // 能力ID 获取话术推荐结果
    const PROGRAM_GET_RECOMMEND_DIALOG_RESULT = 'input_get_recommend_dialog_result';
    // 能力ID 创建标签匹配任务
    const PROGRAM_CREATE_CUSTOMER_TAG_TASK = 'input_create_customer_tag_task';
    // 能力ID 获取标签任务结果
    const PROGRAM_GET_CUSTOMER_TAG_RESULT = 'input_get_customer_tag_result';
    // 能力ID 创建摘要提取任务
    const PROGRAM_CREATE_SUMMARY_TASK = 'input_create_summary_task';
    // 能力ID 获取摘要提取结果
    const PROGRAM_GET_SUMMARY_RESULT = 'input_get_summary_result';
    // 能力ID 创建情感分析任务
    const PROGRAM_CREATE_SENTIMENT_TASK = 'input_create_sentiment_task';
    // 能力ID 获取情感分析结果
    const PROGRAM_GET_SENTIMENT_RESULT = 'input_get_sentiment_result';
    // 能力ID 会话反垃圾分析-创建分析任务
    const PROGRAM_CREATE_SPAM_TASK = 'input_create_spam_task';
    // 能力ID 会话反垃圾分析-获取任务结果
    const PROGRAM_GET_SPAM_RESULT = 'input_get_spam_result';
    // 能力ID 获取企业授权给应用的知识集列表
    const PROGRAM_KNOWLEDGE_BASE_LIST = 'input_knowledge_base_list';
    // 能力ID 员工或客户名称搜索
    const PROGRAM_SEARCH_CONTACT_OR_CUSTOMER = 'input_search_contact_or_customer';

    // 能力ID 会话分析-客户分析
    const PROGRAM_CONVERSATION_ANALYSIS = 'input_customer_analysis';
    // 能力ID 会话分析-员工质检
    const PROGRAM_STAFF_QUALITY_INSPECTION = 'input_staff_quality_inspection';

    /**
     * @var string 能力ID: 创建知识集
     * <br>
     * 每个企业的知识集总数量不能超过1000
     * <br>
     * 暂不支持添加在线文档/表格/微盘文件
     * <br>
     * 应用通过API创建的知识集，自动出现在企业授权给应用的知识集列表中。企业也可以取消授权。
     */
    const PROGRAM_KNOWLEDGE_BASE_CREATE = 'input_knowledge_base_create';

    // 能力ID 获取知识集详情
    const PROGRAM_KNOWLEDGE_BASE_DETAIL = 'input_knowledge_base_detail';
    // 能力ID 添加知识集內容
    const PROGRAM_KNOWLEDGE_BASE_ADD_DOC = 'input_knowledge_base_add_doc';
    // 能力ID 删除知识集內容
    const PROGRAM_KNOWLEDGE_BASE_REMOVE_DOC = 'input_knowledge_base_remove_doc';
    // 能力ID 修改知识集名称
    const PROGRAM_KNOWLEDGE_BASE_MODIFY_NAME = 'input_knowledge_base_modify_name';
    // 能力ID 删除知识集
    const PROGRAM_KNOWLEDGE_BASE_DELETE = 'input_knowledge_base_delete';

    /**
     * @param $msg
     * @return true
     * @throws ErrException
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPExchangeException
     * @throws \AMQPQueueException
     * @throws \yii\db\Exception
     */
    public static function eventHandle($msg)
    {
        // 应用接收专区通知 : https://developer.work.weixin.qq.com/document/path/100048
        if (!empty($msg['MsgType'])) {
            if ($msg['MsgType'] == self::PROGRAM_MSG_TYPE_EVENT) { // MsgType 消息类型，此时固定为：event

                switch ($msg['Event']) {
                    case self::PROGRAM_EVENT_PROGRAM_NOTIFY: // Event 事件类型，此时固定为：program_notify
                        # 数据专区事件
                        $config = SuiteCorpConfig::find()->where(['corp_id' => $msg['ToUserName']])->select('id,suite_id,corp_id')->asArray()->one();
                        if ($config) {
                            $responseData = self::asyncJob($config['suite_id'], $config['corp_id'], $msg['NotifyId']);
                            self::notifyEvent($responseData, $msg['ToUserName'], $config);
                        }
                    break;

                    case self::EVENT_CHANGE_APP_ADMIN: // 应用管理员变更通知
                        SuiteCorpConfigAdminListService::eventChangeAuth(['AuthCorpId' => $msg['ToUserName']]);
                    break;
                }
            }
        }
        return true;
    }

    /**
     * 通知事件
     * @param $notifyResponseData
     * @param $ToUserName
     * @param $config
     * @return void
     * @throws ErrException
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPExchangeException
     * @throws \AMQPQueueException
     */
    public static function notifyEvent($notifyResponseData, $ToUserName, $config)
    {
        \Yii::warning('获取回调数据能力-通知事件：' . json_encode($notifyResponseData, JSON_UNESCAPED_UNICODE));
        if (!empty($notifyResponseData['event_type'])) {
            switch ($notifyResponseData['event_type']) {

                case SuiteService::EVENT_INFO_TYPE_CONVERSATION_NEW_MESSAGE: // 推送事件类型：新消息
                    $exchange                     = self::PROGRAM_MSG_NOTICE_MQ_EXCHANGE . $ToUserName;
                    $queue                        = self::PROGRAM_MSG_NOTICE_MQ_QUEUE . $ToUserName;
                    $routingKey                   = self::PROGRAM_MSG_NOTICE_MQ_ROUTING_KEY . $ToUserName;
                    $notifyResponseData['config'] = $config;
                    // 批量推送到MQ
                    self::pushRabbitMQMsg($exchange, $queue, function ($mq) use ($notifyResponseData, $routingKey) {
                        try {
                            $mq->publish(json_encode($notifyResponseData, JSON_UNESCAPED_UNICODE), $routingKey);
                        } catch (\Exception $e) {
                            \Yii::warning($e->getMessage());
                        }
                    }, $routingKey);
                break;

                case SuiteService::EVENT_INFO_TYPE_HIT_KEYWORD: // 推送事件类型：命中关键词规则通知
                    $routingKey                   = self::PROGRAM_HIT_KEYWORD_NOTICE_MQ_ROUTING_KEY;
                    $notifyResponseData['config'] = $config;
                    // 批量推送到MQ
                    self::pushRabbitMQMsg(self::PROGRAM_HIT_KEYWORD_NOTICE_MQ_EXCHANGE, self::PROGRAM_HIT_KEYWORD_NOTICE_MQ_QUEUE,
                        function ($mq) use ($notifyResponseData, $routingKey) {
                            try {
                                $mq->publish(json_encode($notifyResponseData, JSON_UNESCAPED_UNICODE), $routingKey);
                            } catch (\Exception $e) {
                                \Yii::warning($e->getMessage());
                            }
                        }, $routingKey);
                break;


                case SuiteService::EVENT_INFO_TYPE_AUTH_KNOWLEDGE_BASE:
                    SuiteKnowledgeBaseService::event_auth_knowledge_base($notifyResponseData, $config);
                break;

                case SuiteService::EVENT_INFO_TYPE_UNAUTH_KNOWLEDGE_BASE:
                case SuiteService::EVENT_INFO_TYPE_DELETE_KNOWLEDGE_BASE:
                    SuiteKnowledgeBaseService::event_del_knowledge_base($notifyResponseData, $config);
                break;


                case SuiteService::EVENT_INFO_TYPE_LEARN_DONE_KNOWLEDGE_BASE: // 推送事件类型：知识集 內容学习完成(每个內容学习完成都会回调一次)

                break;

                case SuiteService::EVENT_INFO_TYPE_CHAT_ARCHIVE_SINGLE: // 推送事件类型：客户同意进行聊天内容存档事件回调 - 当客户在单聊中同意存档

                break;

                case SuiteService::EVENT_INFO_TYPE_CHAT_ARCHIVE_ROOM: // 推送事件类型：客户同意进行聊天内容存档事件回调 - 当客户在群聊中同意存档

                break;

                case SuiteService::EVENT_INFO_TYPE_CHAT_ARCHIVE_EXPORT_FINISHED: // 推送事件类型：会话内容导出完成通知

                break;

            }
        }
    }

    /**
     * 设置专区接收回调事件
     * @param $suiteId
     * @param $corpId
     * @return mixed
     * @throws ErrException
     */
    public static function setReceiveCallback($suiteId, $corpId)
    {
        $input = [
            'program_id' => \Yii::$app->params["workWechat"]['programId']
        ];
        $url   = "https://qyapi.weixin.qq.com/cgi-bin/chatdata/set_receive_callback?" . http_build_query(['access_token' => SuiteService::getSuiteCorpToken($suiteId, $corpId)]);
        $data  = sendCurl($url, 'POST', json_encode($input, JSON_UNESCAPED_UNICODE));
        return SuiteService::resultData($data);
    }

    /**
     * 获取回调数据能力（应用接收专区通知，通过NotifyId请求应用同步调用专区程序接口调用专区程序）
     * @param $suiteId
     * @param $corpId
     * @param $notifyId
     * @return array|mixed
     * @throws ErrException
     */
    public static function asyncJob($suiteId, $corpId, $notifyId)
    {
        $input = [
            "program_id"   => \Yii::$app->params["workWechat"]['programId'],
            "ability_id"   => self::PROGRAM_ABILITY_DO_ASYNC_JOB,
            "notify_id"    => $notifyId,
            "request_data" => json_encode([
                "input" => [
                    "func"     => "do_async_job",
                    "func_req" => new \stdClass()
                ]
            ], JSON_UNESCAPED_UNICODE)
        ];
        return self::syncCallProgram($suiteId, $corpId, $input);
    }

    /**
     * 应用同步调用专区程序
     * @param $suiteId
     * @param $corpId
     * @param $input
     * @return array|mixed
     * @throws ErrException
     */
    public static function syncCallProgram($suiteId, $corpId, $input, $isThrow = false)
    {
        $url  = "https://qyapi.weixin.qq.com/cgi-bin/chatdata/sync_call_program?" . http_build_query(['access_token' => SuiteService::getSuiteCorpToken($suiteId, $corpId)]);
        $body = json_encode($input, JSON_UNESCAPED_UNICODE);
        if (\Yii::$app->id != "console") {
            \Yii::warning(sprintf('能力ID：%s，入参：%s', $input['ability_id'], $body));
        } else {
            echo sprintf('[%s] 能力ID：%s，入参：%s', self::getMicroDatetime(), $input['ability_id'], $body) . PHP_EOL;
        }
        $data = sendCurl($url, 'POST', $body);
        if (\Yii::$app->id != "console") {
            \Yii::warning(sprintf('能力ID：%s，出参：%s', $input['ability_id'], $data));
        } else {
            echo sprintf('[%s] 能力ID：%s，出参：%s', self::getMicroDatetime(), $input['ability_id'], $data) . PHP_EOL;
        }
        $data = json_decode($data, true);
        if ($isThrow) {
            return $data;
        }

        $return = [];
        if (!empty($data['response_data'])) {
            if (is_string($data['response_data'])) {
                $return = json_decode($data['response_data'], true);
            } else {
                $return = $data['response_data'];
            }
        }
        return $return;
    }

    /**
     * 执行同步调用专区程序
     * @param string $suiteId 服务商ID
     * @param string $corpId 企业ID
     * @param string $abilityId 能力ID
     * @param array|null $params 请求参数，与专区文档结构保持一致
     * @param bool $isThrow 是否抛出异常
     * @return array|mixed
     * @throws ErrException
     * @see https://developer.work.weixin.qq.com/document/path/99819
     */
    public static function executionSyncCallProgram($suiteId, $corpId, $abilityId, $params = null, $isThrow = false)
    {
        $requestData = [
            "input" => [
                // 能力ID = input_ + func  ，此处去除 input_ (6个字符)
                "func" => substr($abilityId, 6),
            ]
        ];
        if ($params !== null) {
            $requestData['input']['func_req'] = $params;
        }
        $input = [
            "program_id"   => \Yii::$app->params["workWechat"]['programId'],
            "ability_id"   => $abilityId,
            "request_data" => json_encode($requestData, JSON_UNESCAPED_UNICODE)
        ];
        $api   = self::syncCallProgram($suiteId, $corpId, $input, $isThrow);

        logger()->info('[executionSyncCallProgram]', [
            'input' => $input,
            'response' => $api,
        ]);

        if (!$isThrow) {
            return $api;
        }
        if (isset($api['errcode'])) {
            if ($api['errcode'] != '0') {
                throw new ErrException(Code::WRONG_REQUEST, ErrCode::message($api['errcode']));
            }
        }
        $return = [];
        if (!empty($api['response_data'])) {
            if (is_string($api['response_data'])) {
                $return = json_decode($api['response_data'], true);
            } else {
                $return = $api['response_data'];
            }
            if (isset($return['errcode'])) {
                if ($return['errcode'] != '0') {
                    throw new ErrException(Code::WRONG_REQUEST, ErrCode::message($return['errcode']));
                }
            }
        }
        return $return;
    }

    /**
     * 应用异步调用专区程序
     * @param $suiteId
     * @param $corpId
     * @param $abilityId
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function asyncProgramTask($suiteId, $corpId, $abilityId, $params)
    {
        $requestData = [
            "input" => [
                "func" => substr($abilityId, 6),
            ]
        ];
        if ($params !== null) {
            $requestData['input']['func_req'] = $params;
        }
        $input = [
            "program_id"   => \Yii::$app->params["workWechat"]['programId'],
            "ability_id"   => $abilityId,
            "request_data" => json_encode($requestData, JSON_UNESCAPED_UNICODE)
        ];
        $url   = "https://qyapi.weixin.qq.com/cgi-bin/chatdata/async_program_task?" . http_build_query(['access_token' => SuiteService::getSuiteCorpToken($suiteId, $corpId)]);
        $body  = json_encode($input, JSON_UNESCAPED_UNICODE);
        self::outputLog(sprintf('能力ID：%s，入参：%s', $input['ability_id'], $body));
        $data = sendCurl($url, 'POST', $body);
        return SuiteService::resultData($data);
    }

    /**
     * 获取专区程序任务结果
     * @param $suiteId
     * @param $corpId
     * @param $jobId
     * @return mixed
     * @throws ErrException
     */
    public static function asyncProgramResult($suiteId, $corpId, $jobId)
    {
        $url  = "https://qyapi.weixin.qq.com/cgi-bin/chatdata/async_program_result?" . http_build_query(['access_token' => SuiteService::getSuiteCorpToken($suiteId, $corpId)]);
        $body = json_encode(["jobid" => $jobId], JSON_UNESCAPED_UNICODE);
        self::outputLog(sprintf('方法：%s，入参：%s', 'asyncProgramResult', $body));
        $data = sendCurl($url, 'POST', $body);
        return SuiteService::resultData($data);
    }

    /**
     * 上传临时文件到专区
     * @param $suiteId
     * @param $corpId
     * @param $jobId
     * @return mixed
     * @throws ErrException
     */
    public static function asyncUploadMedia($suiteId, $corpId, $type, $media)
    {
        $url  = "https://qyapi.weixin.qq.com/cgi-bin/chatdata/upload_media?" . http_build_query(['access_token' => SuiteService::getSuiteCorpToken($suiteId, $corpId),'type' => $type]);
        $ch  = curl_init($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'media' => new \CURLFile($media['tmp_name'], $media['type'], $media['name'])
        ]);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: multipart/form-data',
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

}