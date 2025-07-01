<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpConfig;
use common\models\SuiteCorpExternalContact;
use common\models\SuiteCorpGroupChat;

class SuiteService extends Service
{

    // 推送事件类型：ticket
    const EVENT_INFO_TYPE_TICKET = 'suite_ticket';

    // 推送事件类型：change_auth （变更授权通知）
    const EVENT_INFO_TYPE_CHANGE_AUTH = 'change_auth';

    // 推送事件类型：新增认证企业授权
    const EVENT_INFO_TYPE_CREATE_AUTH = 'create_auth';

    // 推送事件类型：重置永久代码
    const EVENT_INFO_TYPE_RESET_PERMANENT_CODE = 'reset_permanent_code';

    // 推送事件类型：对话新消息
    const EVENT_INFO_TYPE_CONVERSATION_NEW_MESSAGE = 'conversation_new_message';

    // 推送事件类型：客户同意进行聊天内容存档事件回调 - 当客户在单聊中同意存档
    const EVENT_INFO_TYPE_CHAT_ARCHIVE_SINGLE = 'chat_archive_audit_approved_single';
    // 推送事件类型：客户同意进行聊天内容存档事件回调 - 当客户在群聊中同意存档
    const EVENT_INFO_TYPE_CHAT_ARCHIVE_ROOM = 'chat_archive_audit_approved_room';

    // 推送事件类型：命中关键词规则通知
    const EVENT_INFO_TYPE_HIT_KEYWORD = 'hit_keyword';

    // 推送事件类型：知识集 內容学习完成(每个內容学习完成都会回调一次)
    const EVENT_INFO_TYPE_LEARN_DONE_KNOWLEDGE_BASE = 'knowledge_base_learn_done';

    /** @var string 推送事件类型：授权知识集 */
    const EVENT_INFO_TYPE_AUTH_KNOWLEDGE_BASE = 'auth_knowledge_base';

    /** @var string 推送事件类型：取消授权知识集 */
    const EVENT_INFO_TYPE_UNAUTH_KNOWLEDGE_BASE = 'unauth_knowledge_base';

    /** @var string 推送事件类型：删除授权的知识集 */
    const EVENT_INFO_TYPE_DELETE_KNOWLEDGE_BASE = 'delete_knowledge_base';

    // 推送事件类型：会话内容导出完成通知
    const EVENT_INFO_TYPE_CHAT_ARCHIVE_EXPORT_FINISHED = 'chat_archive_export_finished';

    // 群组名称处理 队列信息
    const GROUP_NAME_MQ_EXCHANGE    = 'aaw.group.name.notice.dir.ex';
    const GROUP_NAME_MQ_QUEUE       = 'aaw.group.name.notice.que';
    const GROUP_NAME_MQ_ROUTING_KEY = 'aaw.group.name.notice.rk';

    /**
     * 根据服务商ID获取企业微信后台推送的ticket
     * @param $suiteId
     * @return mixed|string
     * @throws ErrException
     */
    public static function getSuiteTicket($suiteId)
    {
        $redisKey    = \Yii::$app->params["redisPrefix"] . 'suite.ticket.' . $suiteId;
        $data        = \Yii::$app->redis->get($redisKey);
        $suiteTicket = '';
        if (!empty($data)) {
            $data        = json_decode($data, true);
            $suiteTicket = $data['SuiteTicket'] ?? '';
        }

        return $suiteTicket;
    }

    /**
     * 设置企业微信后台推送的ticket
     * @param $data
     * @return true
     */
    public static function setSuiteTicket($data)
    {
        $redisKey = \Yii::$app->params["redisPrefix"] . 'suite.ticket.' . $data['SuiteId'];
        $redis    = \Yii::$app->redis;
        $redis->set($redisKey, json_encode($data, JSON_UNESCAPED_UNICODE));
        $redis->expire($redisKey, 30 * 24 * 60 * 60); //30天内有效
        return true;
    }

    /**
     * @param $suiteId
     * @param $corpId
     * @return mixed
     * @throws ErrException
     */
    public static function getTicket($suiteId, $corpId)
    {
        $redisKey     = \Yii::$app->params["redisPrefix"] . 'jsapi.ticket.' . $suiteId . '.' . $corpId;
        $redis        = \Yii::$app->redis;
        $tokenJsonStr = $redis->get($redisKey);
        if (empty($tokenJsonStr)) {
            $url          = 'https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
            $tokenJsonStr = sendCurl($url, 'GET');
            $data         = self::resultData($tokenJsonStr);
            $redis->set($redisKey, $tokenJsonStr);
            $redis->expire($redisKey, 3000); //50分钟内有效
        } else {
            $data = json_decode($tokenJsonStr, true);
        }
        return $data['ticket'];
    }

    /**
     * @param $suiteId
     * @param $corpId
     * @return mixed
     * @throws ErrException
     */
    public static function getJsapiTicket($suiteId, $corpId)
    {
        $redisKey     = \Yii::$app->params["redisPrefix"] . 'js.ticket.' . $suiteId . '.' . $corpId;
        $redis        = \Yii::$app->redis;
        $tokenJsonStr = $redis->get($redisKey);
        if (empty($tokenJsonStr)) {
            $url          = 'https://qyapi.weixin.qq.com/cgi-bin/ticket/get?' . http_build_query([
                'access_token' => self::getSuiteCorpToken($suiteId, $corpId),
                'type'         => 'agent_config'
            ]);
            $tokenJsonStr = sendCurl($url, 'GET');
            $data         = self::resultData($tokenJsonStr);
            $redis->set($redisKey, $tokenJsonStr);
            $redis->expire($redisKey, 3000); //50分钟内有效
        } else {
            $data = json_decode($tokenJsonStr, true);
        }
        return $data['ticket'];
    }

    /**
     * @param $suiteId
     * @return mixed
     * @throws ErrException
     */
    public static function getSuiteAccessToken($suiteId)
    {
        $redisKey     = \Yii::$app->params["redisPrefix"] . 'suite.token.' . $suiteId;
        $redis        = \Yii::$app->redis;
        $tokenJsonStr = $redis->get($redisKey);
        if (empty($tokenJsonStr)) {
            $params['suite_id']     = $suiteId;
            $params['suite_secret'] = \Yii::$app->params["workWechat"]['suiteRelation'][$suiteId];
            $params['suite_ticket'] = self::getSuiteTicket($suiteId);
            $tokenJsonStr           = sendCurl('https://qyapi.weixin.qq.com/cgi-bin/service/get_suite_token', 'POST', json_encode($params));
            $respJson               = json_decode($tokenJsonStr, true);
            if (empty($respJson['suite_access_token'])) {
                throw new ErrException(Code::CALL_EXCEPTION, $respJson['errmsg']);
            }
            $redis->set($redisKey, $tokenJsonStr);
            $redis->expire($redisKey, 1 * 60 * 60); //1小时内有效
        } else {
            $respJson = json_decode($tokenJsonStr, true);
        }
        \Yii::warning('服务商获取 AccessToken:' . $tokenJsonStr);
        return $respJson['suite_access_token'];
    }

    /**
     * 获取服务商凭证
     * @return mixed
     */
    public static function getProviderToken()
    {
        $corpId       = \Yii::$app->params["workWechat"]['corpId'];
        $redisKey     = \Yii::$app->params["redisPrefix"] . 'suite.provider.token.' . $corpId;
        $redis        = \Yii::$app->redis;
        $tokenJsonStr = $redis->get($redisKey);
        if (empty($tokenJsonStr)) {
            $tokenJsonStr = sendCurl('https://qyapi.weixin.qq.com/cgi-bin/service/get_provider_token', 'POST', json_encode([
                'corpid'          => $corpId,
                'provider_secret' => \Yii::$app->params["workWechat"]['suiteProviderSecret']
            ]));
            $respJson     = json_decode($tokenJsonStr, true);
            if (empty($respJson['provider_access_token'])) {
                throw new ErrException(Code::CALL_EXCEPTION, '获取服务商凭证异常');
            }
            $redis->set($redisKey, $tokenJsonStr);
            $redis->expire($redisKey, 1 * 60 * 60); //1小时内有效
        } else {
            $respJson = json_decode($tokenJsonStr, true);
        }
        \Yii::warning('获取服务商凭证 AccessToken:' . $tokenJsonStr);
        return $respJson['provider_access_token'];
    }

    /**
     * 获取订单中的账号列表
     * @param $orderId
     * @return mixed
     * @throws ErrException
     */
    public static function licenseListOrderAccount($orderId)
    {
        $url      = 'https://qyapi.weixin.qq.com/cgi-bin/license/list_order_account?' . http_build_query(['provider_access_token' => self::getProviderToken()]);
        $respJson = sendCurl($url, 'POST', json_encode(['order_id' => $orderId, 'limit' => 1000], JSON_UNESCAPED_UNICODE));
        return self::resultData($respJson);
    }

    /**
     * 获取激活码详情
     * @param $corpId
     * @param $activeCode
     * @return mixed
     * @throws ErrException
     */
    public static function licenseGetActiveInfoByCode($corpId, $activeCode)
    {
        $url      = 'https://qyapi.weixin.qq.com/cgi-bin/license/get_active_info_by_code?' . http_build_query(['provider_access_token' => self::getProviderToken()]);
        $respJson = sendCurl($url, 'POST', json_encode(['corpid' => $corpId, 'active_code' => $activeCode], JSON_UNESCAPED_UNICODE));
        return self::resultData($respJson);
    }

    /**
     * 批量获取激活码详情
     * @param $corpId
     * @param $activeCode
     * @return mixed
     * @throws ErrException
     */
    public static function licenseBatchGetActiveInfoByCode($corpId, $activeCode)
    {
        $url      = 'https://qyapi.weixin.qq.com/cgi-bin/license/batch_get_active_info_by_code?' . http_build_query(['provider_access_token' => self::getProviderToken()]);
        $respJson = sendCurl($url, 'POST', json_encode(['corpid' => $corpId, 'active_code_list' => $activeCode], JSON_UNESCAPED_UNICODE));
        return self::resultData($respJson);
    }

    /**
     * 获取企业的账号列表
     * @param $corpId
     * @return mixed
     * @throws ErrException
     */
    public static function licenseListActiveAccount($corpId)
    {
        $url      = 'https://qyapi.weixin.qq.com/cgi-bin/license/list_actived_account?' . http_build_query(['provider_access_token' => self::getProviderToken()]);
        $respJson = sendCurl($url, 'POST', json_encode(['corpid' => $corpId, 'limit' => 1000], JSON_UNESCAPED_UNICODE));
        return self::resultData($respJson);
    }

    /**
     * 获取成员的激活详情
     * @param $corpId
     * @param $userid
     * @return mixed
     * @throws ErrException
     */
    public static function licenseGetActiveInfoByUser($corpId, $userid)
    {
        $url      = 'https://qyapi.weixin.qq.com/cgi-bin/license/get_active_info_by_user?' . http_build_query(['provider_access_token' => self::getProviderToken()]);
        $respJson = sendCurl($url, 'POST', json_encode(['corpid' => $corpId, 'userid' => $userid], JSON_UNESCAPED_UNICODE));
        return self::resultData($respJson);
    }

    /**
     * 账号继承
     * @param $corpId
     * @param $transferList
     * @return mixed
     * @throws ErrException
     */
    public static function licenseBatchTransferLicense($corpId, $transferList)
    {
        $url      = 'https://qyapi.weixin.qq.com/cgi-bin/license/batch_transfer_license?' . http_build_query(['provider_access_token' => self::getProviderToken()]);
        $respJson = sendCurl($url, 'POST', json_encode(['corpid' => $corpId, 'transfer_list' => $transferList], JSON_UNESCAPED_UNICODE));
        return self::resultData($respJson);
    }

    /**
     * 获取订单详情
     * @param $orderId
     * @return mixed
     * @throws ErrException
     */
    public static function licenseOrder($orderId)
    {
        $url      = 'https://qyapi.weixin.qq.com/cgi-bin/license/get_order?' . http_build_query(['provider_access_token' => self::getProviderToken()]);
        $respJson = sendCurl($url, 'POST', json_encode(['order_id' => $orderId], JSON_UNESCAPED_UNICODE));
        return self::resultData($respJson);
    }

    /**
     * 获取多企业订单详情
     * @param $orderId
     * @return mixed
     * @throws ErrException
     */
    public static function licenseUnionOrder($orderId)
    {
        $url      = 'https://qyapi.weixin.qq.com/cgi-bin/license/get_union_order?' . http_build_query(['provider_access_token' => self::getProviderToken()]);
        $respJson = sendCurl($url, 'POST', json_encode(['order_id' => $orderId, 'limit' => 1000], JSON_UNESCAPED_UNICODE));
        return self::resultData($respJson);
    }

    /**
     * 获取订单列表
     * @return mixed
     * @throws ErrException
     */
    public static function licenseListOrder($params)
    {
        $url      = 'https://qyapi.weixin.qq.com/cgi-bin/license/list_order?' . http_build_query(['provider_access_token' => self::getProviderToken()]);
        $respJson = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($respJson);
    }

    /**
     * 下单购买账号
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function licenseCreateNewOrder($params)
    {
        $url      = 'https://qyapi.weixin.qq.com/cgi-bin/license/create_new_order?' . http_build_query(['provider_access_token' => self::getProviderToken()]);
        $respJson = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($respJson);
    }

    /**
     * 取消订单
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function licenseCancelOrder($params)
    {
        $url      = 'https://qyapi.weixin.qq.com/cgi-bin/license/cancel_order?' . http_build_query(['provider_access_token' => self::getProviderToken()]);
        $respJson = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($respJson);
    }

    /**
     * 创建续期任务
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function licenseCreateRenewOrderJob($params)
    {
        $url      = 'https://qyapi.weixin.qq.com/cgi-bin/license/create_renew_order_job?' . http_build_query(['provider_access_token' => self::getProviderToken()]);
        $respJson = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($respJson);
    }

    /**
     * 提交续期订单
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function licenseSubmitOrderJob($params)
    {
        $url      = 'https://qyapi.weixin.qq.com/cgi-bin/license/submit_order_job?' . http_build_query(['provider_access_token' => self::getProviderToken()]);
        $respJson = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($respJson);
    }

    /**
     * 创建多企业新购任务
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function licenseCreateNewOrderJob($params)
    {
        $url      = 'https://qyapi.weixin.qq.com/cgi-bin/license/create_new_order_job?' . http_build_query(['provider_access_token' => self::getProviderToken()]);
        $respJson = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($respJson);
    }

    /**
     * 提交多企业新购订单
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function licenseSubmitNewOrderJob($params)
    {
        $url      = 'https://qyapi.weixin.qq.com/cgi-bin/license/submit_new_order_job?' . http_build_query(['provider_access_token' => self::getProviderToken()]);
        $respJson = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($respJson);
    }

    /**
     * 获取多企业新购订单提交结果
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function licenseNewOrderJobResult($jobId)
    {
        $url      = 'https://qyapi.weixin.qq.com/cgi-bin/license/new_order_job_result?' . http_build_query(['provider_access_token' => self::getProviderToken()]);
        $respJson = sendCurl($url, 'POST', json_encode(['jobid' => $jobId], JSON_UNESCAPED_UNICODE));
        return self::resultData($respJson);
    }

    /**
     * 激活账号
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function licenseActiveAccount($params)
    {
        $url      = 'https://qyapi.weixin.qq.com/cgi-bin/license/active_account?' . http_build_query(['provider_access_token' => self::getProviderToken()]);
        $respJson = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($respJson);
    }

    /**
     * 批量激活账号
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function licenseBatchActiveAccount($params)
    {
        $url      = 'https://qyapi.weixin.qq.com/cgi-bin/license/batch_active_account?' . http_build_query(['provider_access_token' => self::getProviderToken()]);
        $respJson = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($respJson);
    }

    /**
     * 处理企业微信Api返回结果
     * @param $data
     * @return mixed
     * @throws ErrException
     */
    public static function resultData($data)
    {
        // 获取调用栈信息
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        // 检查调用栈是否有足够的层级 并 获取上一个调用层级的信息
        if (count($trace) > 1 && $previousCall = $trace[1]) {
            // 检查是否是类方法调用
            $methodName = $previousCall['function'];
        }
        self::outputLog(sprintf('方法：%s，返回：%s', $methodName ?? '未知方法', $data));
        if (!is_array($data)) {
            $data = json_decode($data, true);
        }
        if (empty($data)) {
            throw new ErrException(Code::DATA_ERROR, '企微微信请求失败！');
        }
        if (isset($data['errcode']) && $data['errcode'] != 0) {
            $msg = $data['errmsg'] ?? '原因未知';
            throw new ErrException(Code::DATA_ERROR, '企微微信请求失败：' . $msg);
        }
        return $data;
    }

    /**
     * @param $suiteId
     * @return mixed
     * @throws ErrException
     */
    public static function getSuitePreAuthCode($suiteId)
    {
        $redisKey     = \Yii::$app->params["redisPrefix"] . 'suite.auth.code.' . $suiteId;
        $redis        = \Yii::$app->redis;
        $tokenJsonStr = $redis->get($redisKey);
        if (empty($tokenJsonStr)) {
            $url          = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_pre_auth_code?' . http_build_query(['suite_access_token' => self::getSuiteAccessToken($suiteId)]);
            $tokenJsonStr = sendCurl($url, 'GET');
            $data         = self::resultData($tokenJsonStr);
            $redis->set($redisKey, $tokenJsonStr);
            $redis->expire($redisKey, 1 * 60 * 60); //1小时内有效
        } else {
            $data = json_decode($tokenJsonStr, true);
        }
        return $data['pre_auth_code'];
    }

    /**
     * @param $suiteId
     * @param $authType
     * @return mixed
     * @throws ErrException
     */
    public static function setSuiteSessionInfo($suiteId, $authType = 1)
    {
        $url          = 'https://qyapi.weixin.qq.com/cgi-bin/service/set_session_info?' . http_build_query(['suite_access_token' => self::getSuiteAccessToken($suiteId)]);
        $tokenJsonStr = sendCurl($url, 'POST', json_encode([
            'pre_auth_code' => self::getSuitePreAuthCode($suiteId),
            'session_info'  => [
                'appid'     => [],
                'auth_type' => $authType
            ],
        ], JSON_UNESCAPED_UNICODE));
        return self::resultData($tokenJsonStr);
    }

    /**
     * 获取企业永久授权码 V2版本
     * https://developer.work.weixin.qq.com/document/path/100776
     * @param $msg
     * @return true
     * @throws ErrException
     * @throws \yii\db\Exception
     */
    public static function setSuitePermanentCode($msg)
    {
        $url          = 'https://qyapi.weixin.qq.com/cgi-bin/service/v2/get_permanent_code?' . http_build_query(['suite_access_token' => self::getSuiteAccessToken($msg['SuiteId'])]);
        $tokenJsonStr = sendCurl($url, 'POST', json_encode(['auth_code' => $msg['AuthCode']], JSON_UNESCAPED_UNICODE));
        \Yii::warning('获取永久授权码，resp：' . $tokenJsonStr);
        $tokenArray = json_decode($tokenJsonStr, true);
        if (empty($tokenArray['auth_corp_info'])) {
            throw new ErrException(Code::PARAMS_ERROR, '未获取到授权企业');
        }
        $suiteId = $msg['SuiteId'];
        $corpId  = $tokenArray['auth_corp_info']['corpid'];
        $params  = [
            'suite_id'             => $suiteId,
            'corp_id'              => $corpId,
            'corp_name'            => $tokenArray['auth_corp_info']['corp_name'],
            'suite_permanent_code' => $tokenArray['permanent_code'],
            'updated_at'           => time(),
            'creator'              => $tokenArray['auth_user_info']['userid'],
        ];

        $data = SuiteService::getAuthInfo($suiteId, $corpId, $tokenArray['permanent_code']);
        if (!empty($data['auth_corp_info'])) {
            $authCorpInfo                = $data['auth_corp_info'];
            $params['corp_scale']        = $authCorpInfo['corp_scale'] ?: '';
            $params['corp_industry']     = $authCorpInfo['corp_industry'] ?: '';
            $params['corp_sub_industry'] = $authCorpInfo['corp_sub_industry'] ?: '';
            $params['subject_type']      = $authCorpInfo['subject_type'] ?: SuiteCorpConfig::SUBJECT_TYPE_1;
            $params['corp_type']         = $authCorpInfo['corp_type'] ?: '';
            $params['verified_end_time'] = $authCorpInfo['verified_end_time'] ?: 0;
        }
        if (!empty($data['auth_info'])) {
            $authAgent = $data['auth_info']['agent'];
            foreach ($authAgent as $agent) {
                if ($agent['name'] == \Yii::$app->params["workWechat"]['appName']) {
                    $params['suite_agent_id']   = $agent['agentid'];
                    $params['suite_agent_name'] = $agent['name'];
                }
            }
        }

        return SuiteCorpConfigService::createOrUpdate($params);
    }

    /**
     * 获取企业授权信息
     * @param $suiteId
     * @param $corpId
     * @param $permanentCode
     * @return mixed
     * @throws ErrException
     */
    public static function getAuthInfo($suiteId, $corpId, $permanentCode = '')
    {
        if (empty($permanentCode)) {
            $permanentCode = self::getSuitePermanentCode($suiteId, $corpId);
        }
        $url          = 'https://qyapi.weixin.qq.com/cgi-bin/service/v2/get_auth_info?' . http_build_query(['suite_access_token' => self::getSuiteAccessToken($suiteId)]);
        $tokenJsonStr = sendCurl($url, 'POST', json_encode(['auth_corpid' => $corpId, 'permanent_code' => $permanentCode], JSON_UNESCAPED_UNICODE));
        return self::resultData($tokenJsonStr);
    }

    /**
     * @param $suiteId
     * @param $corpId
     * @return false|string|null
     */
    public static function getSuitePermanentCode($suiteId, $corpId)
    {
        return SuiteCorpConfig::find()->where(['suite_id' => $suiteId, 'corp_id' => $corpId])->select('suite_permanent_code')->limit(1)->scalar();
    }

    /**
     * @param $msg
     * @param $agentId
     * @return true
     */
    public static function eventCallback($msg, $agentId)
    {
        try {
            /* 指令回调URL */
            if (!empty($msg['InfoType'])) {

                switch ($msg['InfoType']) {
                    case self::EVENT_INFO_TYPE_TICKET:
                        self::setSuiteTicket($msg);
                        break;
                    case self::EVENT_INFO_TYPE_CHANGE_AUTH:
                        // SuiteCorpConfigAdminListService::eventChangeAuth($msg);
                        $data = SuiteCorpConfig::find()->select('id,suite_id,corp_id')->andWhere(['suite_id' => $msg['SuiteId'], 'corp_id' => $msg['AuthCorpId']])->limit(1)->asArray()->one();
                        if ($data) {
                            $data['TimeStamp'] = $msg['TimeStamp'];
                            $data['InfoType']  = $msg['InfoType'];

                            $routingKey = SuiteCorpConfigChatAuthService::MQ_FAN_OUT_ROUTING_KEY_CORP_CHANGE_AUTH;
                            // 批量推送到MQ  投递广播消息
                            self::pushRabbitMQMsg(SuiteCorpConfigChatAuthService::MQ_FAN_OUT_EXCHANGE_CORP_CHANGE_AUTH, '', function ($mq) use ($data, $routingKey) {
                                try {
                                    $mq->publish(json_encode($data, JSON_UNESCAPED_UNICODE), $routingKey);
                                } catch (\Exception $e) {
                                    \Yii::warning($e->getMessage());
                                }
                            }, $routingKey, AMQP_EX_TYPE_FANOUT);
                        }
                        break;
                    case self::EVENT_INFO_TYPE_RESET_PERMANENT_CODE:
                    case self::EVENT_INFO_TYPE_CREATE_AUTH:
                        self::setSuitePermanentCode($msg);
                        break;
                    case self::EVENT_INFO_TYPE_CONVERSATION_NEW_MESSAGE:
                        \Yii::warning('推送事件类型：对话新消息,会话存档（弃用）：' . json_encode($msg, JSON_UNESCAPED_UNICODE));
                        break;

                    case SuiteCorpExternalContactService::EVENT_CHANGE_CONTACT:  // 主事件(Event)：通讯录变更通知事件
                        switch ($msg['ChangeType']) {
                            case SuiteCorpExternalContactService::CHANGE_CONTACT_CREATE_PARTY: // 新增部门事件
                            case SuiteCorpExternalContactService::CHANGE_CONTACT_UPDATE_PARTY: // 更新部门事件
                                $departmentDetails                  = self::getDepartmentDetails($msg['SuiteId'], $msg['AuthCorpId'], $msg['Id']);
                                $departmentDetails['suite_id']      = $msg['SuiteId'];
                                $departmentDetails['corp_id']       = $msg['AuthCorpId'];
                                $departmentDetails['department_id'] = $departmentDetails['id'];
                                SuiteCorpDepartmentService::createOrUpdate($departmentDetails);
                                break;

                            case SuiteCorpExternalContactService::CHANGE_CONTACT_DELETE_PARTY: // 删除部门事件
                                SuiteCorpDepartmentService::delete(['suite_id' => $msg['SuiteId'], 'corp_id' => $msg['AuthCorpId'], 'department_id' => $msg['Id'], 'deleted_at' => $msg['TimeStamp']]);
                                break;

                            case SuiteCorpExternalContactService::CHANGE_CONTACT_CREATE_USER: // 新增成员事件
                            case SuiteCorpExternalContactService::CHANGE_CONTACT_UPDATE_USER: // 更新成员事件
                                $user = SuiteService::getDkUser($msg['SuiteId'], $msg['AuthCorpId'], $msg['UserID']);
                                SuiteCorpAccountService::syncAccountInfo($msg['SuiteId'], $msg['AuthCorpId'], $user);
                                break;

                            case SuiteCorpExternalContactService::CHANGE_CONTACT_DELETE_USER: // 删除成员事件
                                SuiteCorpAccountService::delete(['suite_id' => $msg['SuiteId'], 'corp_id' => $msg['AuthCorpId'], 'userid' => $msg['UserID'], 'deleted_at' => $msg['TimeStamp']]);
                                break;

                            case SuiteCorpExternalContactService::CHANGE_CONTACT_UPDATE_TAG: // 标签变更通知
                                break;
                        }
                        break;

                    case SuiteCorpExternalContactService::EVENT_EXTERNAL_CHAT:  // 主事件(Event)：客户群事件
                        switch ($msg['ChangeType']) {
                            case SuiteCorpExternalContactService::CHAT_CHANGE_TYPE_CREATE: // 客户群创建事件
                            case SuiteCorpExternalContactService::CHAT_CHANGE_TYPE_UPDATE: // 客户群变更事件
                                $details             = SuiteService::getExternalContactGroupChat($msg['SuiteId'], $msg['AuthCorpId'], $msg['ChatId']);
                                $details             = $details['group_chat'];
                                $details['suite_id'] = $msg['SuiteId'];
                                $details['corp_id']  = $msg['AuthCorpId'];
                                SuiteCorpGroupChatService::create($details);
                                break;
                            case SuiteCorpExternalContactService::CHAT_CHANGE_TYPE_DISMISS: // 客户群解散事件
                                SuiteCorpGroupChatService::updateByChatId(['suite_id' => $msg['SuiteId'], 'corp_id' => $msg['AuthCorpId'], 'is_dismiss' => SuiteCorpGroupChat::IS_DISMISS_1, 'chat_id' => $msg['ChatId'], 'dismiss_time' => $msg['TimeStamp']]);
                                break;
                        }
                        break;

                    case SuiteCorpExternalContactService::EVENT_EXTERNAL_CONTACT: // 主事件(Event)：变更企业联系人
                        switch ($msg['ChangeType']) {
                            case SuiteCorpExternalContactService::CONTACT_CHANGE_TYPE_ADD: // 添加企业客户事件
                            case SuiteCorpExternalContactService::CONTACT_CHANGE_TYPE_EDIT: // 编辑企业客户事件
                                \Yii::warning('拉取外部联系人数据，企业ID：' . $msg['AuthCorpId'] . ',联系人：' . $msg['ExternalUserID']);
                                $details                                  = SuiteService::getExternalContactDetails($msg['SuiteId'], $msg['AuthCorpId'], $msg['ExternalUserID']);
                                $details['external_contact']['suite_id']  = $msg['SuiteId'];
                                $details['external_contact']['corp_id']   = $msg['AuthCorpId'];
                                $details['external_contact']['is_modify'] = SuiteCorpExternalContact::IS_MODIFY_2;
                                SuiteCorpExternalContactService::create($details);
                                break;
                            case SuiteCorpExternalContactService::CONTACT_CHANGE_TYPE_DEL: // 删除企业客户事件
                                SuiteCorpExternalContactFollowUserService::delete($msg);
                                break;

                            case SuiteCorpExternalContactService::CONTACT_CHANGE_TYPE_DEL_FOLLOW: // 删除跟进成员事件
                                break;

                            case SuiteCorpExternalContactService::CONTACT_CHANGE_TYPE_ADD_HALF: // 外部联系人免验证添加成员事件
                                break;

                            case SuiteCorpExternalContactService::CONTACT_CHANGE_TYPE_TRANSFER_FAIL: // 客户接替失败事件
                                break;
                        }
                        break;

                    case SuiteCorpExternalContactService::EVENT_EXTERNAL_TAG:  // 主事件(Event)：企业客户标签事件
                        switch ($msg['ChangeType']) {
                            case SuiteCorpExternalContactService::TAG_CHANGE_TYPE_CREATE: // 企业客户标签创建事件
                                break;

                            case SuiteCorpExternalContactService::TAG_CHANGE_TYPE_UPDATE: // 企业客户标签变更事件
                                break;

                            case SuiteCorpExternalContactService::TAG_CHANGE_TYPE_DELETE: // 企业客户标签删除事件
                                break;

                            case SuiteCorpExternalContactService::TAG_CHANGE_TYPE_SHUFFLE: // 企业客户标签重排事件
                                break;
                        }
                        break;
                }
            } elseif (!empty($msg['Event']) && !empty($msg['MsgType'])) {
                /*
                 * 数据专区通知消息类型
                 * MsgType = event && Event = program_notify
                 * {"msgLen":333,"msg":{"ToUserName":"wp7sqIDQAATc2RXt8kMyYj90I_Q0N8sw","FromUserName":"sys","MsgType":"event","Event":"program_notify","CreateTime":"1723857366","NotifyId":"3XQ-ZVnrdFaJfDGLuAt3b1Y_v_s6BKPbO94Iq9ZKSVRC-l8fVm6VDhSnlamqAVTj"},"receiveId":"wp7sqIDQAATc2RXt8kMyYj90I_Q0N8sw"}
                 */
                SuiteProgramService::eventHandle($msg);
            }
        } catch (\Exception $e) {
            \Yii::warning('回调事件处理异常，msg：' . json_encode($msg, JSON_UNESCAPED_UNICODE) . ',错误：' . $e->getMessage());
        }
        return true;
    }

    /**
     * @param $suiteId
     * @param $corpId
     * @param $id
     * @return array|mixed
     * @throws ErrException
     */
    public static function getDepartmentDetails($suiteId, $corpId, $id)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/department/get?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId), 'id' => $id]);
        $data = sendCurl($url, 'GET');
        $data = self::resultData($data);
        return empty($data['department']) ? [] : $data['department'];
    }

    /**
     * @param $suiteId
     * @param $corpId
     * @return array|mixed
     * @throws ErrException
     */
    public static function getDepartmentList($suiteId, $corpId)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/department/list?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
        $data = sendCurl($url, 'GET');
        $data = self::resultData($data);
        return empty($data['department']) ? [] : $data['department'];
    }

    /**
     * 获取访问用户身份
     * @param $suiteId
     * @param $code
     * @return mixed
     * @throws ErrException
     */
    public static function getUserInfo3rd($suiteId, $code)
    {
        $params = [
            'suite_access_token' => self::getSuiteAccessToken($suiteId),
            'code'               => $code
        ];
        $url    = 'https://qyapi.weixin.qq.com/cgi-bin/service/auth/getuserinfo3rd?' . http_build_query($params);
        $data   = sendCurl($url, 'GET');
        return self::resultData($data);
    }

    /**
     * @param $suiteId
     * @param $userTicket
     * @return mixed
     * @throws ErrException
     */
    public static function getUserDetail3rd($suiteId, $userTicket)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/service/auth/getuserdetail3rd?' . http_build_query(['suite_access_token' => self::getSuiteAccessToken($suiteId)]);
        $data = sendCurl($url, 'POST', json_encode(['user_ticket' => $userTicket], JSON_UNESCAPED_UNICODE));
        return self::resultData($data);
    }

    /**
     * @param $suiteId
     * @param $corpId
     * @return array|mixed
     * @throws ErrException
     */
    public static function getDepartmentSimpleList($suiteId, $corpId)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/department/simplelist?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
        $data = sendCurl($url, 'GET');
        $data = self::resultData($data);
        return empty($data['department_id']) ? [] : $data['department_id'];
    }

    /**
     * 获取部门成员详情
     * @param $suiteId
     * @param $corpId
     * @param $departmentId
     * @return array|mixed
     * @throws ErrException
     */
    public static function getDepartmentUsers($suiteId, $corpId, $departmentId)
    {
        $params['access_token']  = self::getSuiteCorpToken($suiteId, $corpId);
        $params['department_id'] = $departmentId;
        $url                     = 'https://qyapi.weixin.qq.com/cgi-bin/user/list?' . http_build_query($params);
        $data                    = sendCurl($url, 'GET');
        $data                    = self::resultData($data);
        return empty($data['userlist']) ? [] : $data['userlist'];
    }

    /**
     * @param $suiteId
     * @param $corpId
     * @param $userId
     * @return mixed
     * @throws ErrException
     */
    public static function getDkUser($suiteId, $corpId, $userId)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/user/get?' . http_build_query([
            'access_token' => self::getSuiteCorpToken($suiteId, $corpId),
            'userid'       => $userId
        ]);
        $data = sendCurl($url, 'GET');
        return self::resultData($data);
    }

    /**
     * 设置公钥
     * @param $suiteId
     * @param $corpId
     * @param $publicKeyVer
     * @return mixed
     * @throws ErrException
     */
    public static function setPublicKey($suiteId, $corpId, $publicKeyVer)
    {
        $params['public_key']     = \Yii::$app->params["workWechat"]['publicKey'];
        $params['public_key_ver'] = $publicKeyVer;
        $url                      = 'https://qyapi.weixin.qq.com/cgi-bin/chatdata/set_public_key?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
        $data                     = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($data);
    }

    /**
     * 获取授权存档的成员列表
     * @param $suiteId
     * @param $corpId
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function getAuthUserList($suiteId, $corpId, $params)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/chatdata/get_auth_user_list?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
        $data = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($data);
    }

    /**
     * 获取客户详情
     * @param $suiteId
     * @param $corpId
     * @param $userid
     * @return mixed
     * @throws ErrException
     */
    public static function getExternalContactList($suiteId, $corpId, $userid)
    {
        $params['access_token'] = self::getSuiteCorpToken($suiteId, $corpId);
        $params['userid']       = $userid;
        $url                    = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/list?' . http_build_query($params);
        $data                   = sendCurl($url, 'GET');
        return self::resultData($data);
    }

    /**
     * @param $suiteId
     * @param $corpId
     * @param $externalUserid
     * @return mixed
     * @throws ErrException
     */
    public static function getExternalContactDetails($suiteId, $corpId, $externalUserid)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/get?' . http_build_query([
            'access_token'    => self::getSuiteCorpToken($suiteId, $corpId),
            'external_userid' => $externalUserid
        ]);
        $data = sendCurl($url, 'GET');
        return self::resultData($data);
    }

    /**
     * @param $suiteId
     * @param $corpId
     * @param $param
     * @return mixed
     * @throws ErrException
     */
    public static function getBatchExternalContactDetails($suiteId, $corpId, $param)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/batch/get_by_user?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
        $data = sendCurl($url, 'POST', json_encode($param, JSON_UNESCAPED_UNICODE));
        return self::resultData($data);
    }

    /**
     * @param $suiteId
     * @param $corpId
     * @return mixed
     * @throws ErrException
     */
    public static function getCorpAuthInfo($suiteId, $corpId)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/chatdata/get_corp_auth_info?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
        $data = sendCurl($url, 'POST');
        return self::resultData($data);
    }

    /**
     * 设置授权应用可见范围
     * @param $suiteId
     * @param $corpId
     * @param $agentid
     * @return mixed
     * @throws ErrException
     */
    public static function setScope($suiteId, $corpId, $agentid)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/agent/set_scope?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
        $data = sendCurl($url, 'POST', json_encode(['agentid' => $agentid], JSON_UNESCAPED_UNICODE));
        return self::resultData($data);
    }

    /**
     * 获取企业凭证
     * @param $suiteId
     * @param $corpId
     * @return mixed
     * @throws ErrException
     */
    public static function getSuiteCorpToken($suiteId, $corpId)
    {
        $redisKey     = \Yii::$app->params["redisPrefix"] . 'suite.corp.token.' . $suiteId . '.' . $corpId;
        $redis        = \Yii::$app->redis;
        $tokenJsonStr = $redis->get($redisKey);
        if (empty($tokenJsonStr)) {
            $url           = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_corp_token?' . http_build_query(['suite_access_token' => self::getSuiteAccessToken($suiteId)]);
            $permanentCode = SuiteService::getSuitePermanentCode($suiteId, $corpId);
            $tokenJsonStr  = sendCurl($url, 'POST', json_encode(['auth_corpid' => $corpId, 'permanent_code' => $permanentCode], JSON_UNESCAPED_UNICODE));
            $resp          = self::resultData($tokenJsonStr);
            $redis->set($redisKey, $tokenJsonStr);
            $redis->expire($redisKey, 1 * 60 * 60); //1小时内有效
        } else {
            $resp = json_decode($tokenJsonStr, true);
        }
        return $resp['access_token'];
    }

    /**
     * 获取应用的管理员列表
     * @param $suiteId
     * @param $corpId
     * @param $agentId
     * @return mixed
     * @throws ErrException
     */
    public static function getCorpAgentAdminList($suiteId, $corpId, $agentId)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_admin_list?' . http_build_query(['suite_access_token' => self::getSuiteAccessToken($suiteId)]);
        $data = sendCurl($url, 'POST', json_encode(['auth_corpid' => $corpId, 'agentid' => $agentId], JSON_UNESCAPED_UNICODE));
        return self::resultData($data);
    }

    /**
     * 获取应用权限详情
     * @param $suiteId
     * @param $corpId
     * @return mixed
     * @throws ErrException
     */
    public static function getCorpAgentPermissions($suiteId, $corpId)
    {
        $params['access_token'] = self::getSuiteCorpToken($suiteId, $corpId);
        $url                    = 'https://qyapi.weixin.qq.com/cgi-bin/agent/get_permissions?' . http_build_query($params);
        $data                   = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($data);
    }

    /**
     * 获取企业授权信息
     * @param $suiteId
     * @param $corpId
     * @return mixed
     * @throws ErrException
     */
    public static function getCorpAgentAuthInfo($suiteId, $corpId)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/service/get_auth_info?' . http_build_query(['suite_access_token' => self::getSuiteAccessToken($suiteId)]);
        $data = sendCurl($url, 'POST', json_encode(["auth_corpid" => $corpId, "permanent_code" => SuiteService::getSuitePermanentCode($suiteId, $corpId)], JSON_UNESCAPED_UNICODE));
        return self::resultData($data);
    }

    /**
     * 延长试用期
     * @param $suiteId
     * @param $corpId
     * @return mixed
     * @throws ErrException
     */
    public static function prolongTry($suiteId, $corpId, $appid)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/service/prolong_try?' . http_build_query(['suite_access_token' => self::getSuiteAccessToken($suiteId)]);
        $data = sendCurl($url, 'POST', json_encode(["buyer_corpid" => $corpId, "prolong_days" => 40, "appid" => (int) $appid], JSON_UNESCAPED_UNICODE));
        return self::resultData($data);
    }

    /**
     * 获取应用管理员列表
     * @param $suiteId
     * @param $corpId
     * @return mixed
     * @throws ErrException
     */
    public static function agentGetAdminList($suiteId, $corpId)
    {
        $params['access_token'] = SuiteService::getSuiteCorpToken($suiteId, $corpId);
        $url                    = 'https://qyapi.weixin.qq.com/cgi-bin/agent/get_admin_list?' . http_build_query($params);
        $respJson               = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($respJson);
    }

    /**
     * 获取企业已购信息
     * @param $corpId
     * @return mixed
     * @throws ErrException
     */
    public static function getCorpBuyInfo($corpId)
    {
        $url      = 'https://qyapi.weixin.qq.com/cgi-bin/advanced_api/get_corp_buy_info?' . http_build_query(['provider_access_token' => self::getProviderToken()]);
        $respJson = sendCurl($url, 'POST', json_encode(['advanced_api_type' => 1, 'custom_corpid' => $corpId], JSON_UNESCAPED_UNICODE));
        return self::resultData($respJson);
    }

    /**
     * 上传临时素材
     * @param $suiteId
     * @param $corpId
     * @param $type
     * @param $media
     * @return bool|string
     * @throws ErrException
     */
    public static function uploadMedia($suiteId, $corpId, $type, $media)
    {
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/media/upload?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId), 'type' => $type]);
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

    /**
     * 获取企业全部的发表列表
     * @param $suiteId
     * @param $corpId
     * @param $param
     * @return mixed
     * @throws ErrException
     */
    public static function getExternalContactMomentList($suiteId, $corpId, $param)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/get_moment_list?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
        $data = sendCurl($url, 'POST', json_encode($param, JSON_UNESCAPED_UNICODE));
        return self::resultData($data);
    }

    /**
     * 获取客户朋友圈的互动数据
     * @param $suiteId
     * @param $corpId
     * @param $param
     * @return mixed
     * @throws ErrException
     */
    public static function getExternalContactMomentComments($suiteId, $corpId, $param)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/get_moment_comments?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
        $data = sendCurl($url, 'POST', json_encode($param, JSON_UNESCAPED_UNICODE));
        return self::resultData($data);
    }

    /**
     * 发送应用消息
     * @param $suiteId
     * @param $corpId
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function messageSend($suiteId, $corpId, $params)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/message/send?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
        $data = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($data);
    }

    /**
     * 配置客户联系「联系我」方式
     * @param $suiteId
     * @param $corpId
     * @param $userid
     * @return mixed
     * @throws ErrException
     */
    public static function getExternalContactAddContactWay($suiteId, $corpId, $userid)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/add_contact_way?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
        $data = sendCurl($url, 'POST', json_encode([
            'type'        => 1,
            'scene'       => 2,
            'skip_verify' => true,
            'user'        => [$userid]
        ], JSON_UNESCAPED_UNICODE));
        return self::resultData($data);
    }

    /**
     * 获取客户群列表
     * @param $suiteId
     * @param $corpId
     * @param $param
     * @return mixed
     * @throws ErrException
     */
    public static function getExternalContactGroupChatList($suiteId, $corpId, $param)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/groupchat/list?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
        $data = sendCurl($url, 'POST', json_encode($param, JSON_UNESCAPED_UNICODE));
        return self::resultData($data);
    }

    /**
     * 获取客户群详情
     * @param $suiteId
     * @param $corpId
     * @param $chatId
     * @return mixed
     * @throws ErrException
     */
    public static function getExternalContactGroupChat($suiteId, $corpId, $chatId)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/groupchat/get?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
        $data = sendCurl($url, 'POST', json_encode(['need_name' => 1, 'chat_id' => $chatId], JSON_UNESCAPED_UNICODE));
        return self::resultData($data);
    }

    /**
     * @param $suiteId
     * @param $corpId
     * @param $mediaId
     * @return bool|string
     * @throws ErrException
     */
    public static function downloadMedia($suiteId, $corpId, $mediaId)
    {
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/media/get?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId), 'media_id' => $mediaId]);
        return sendCurl($url, 'GET');
    }

    /**
     * 离职继承 - 分配离职成员的客户
     * @param $suiteId
     * @param $corpId
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function externalContactResignedTransferCustomer($suiteId, $corpId, $params)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/resigned/transfer_customer?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
        $data = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($data);
    }

    /**
     * 离职继承 - 查询客户接替状态
     * @param $suiteId
     * @param $corpId
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function externalContactResignedTransferResult($suiteId, $corpId, $params)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/resigned/transfer_result?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
        $data = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($data);
    }

    /**
     * 离职继承 - 分配离职成员的客户群
     * @param $suiteId
     * @param $corpId
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function externalContactGroupChatTransfer($suiteId, $corpId, $params)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/groupchat/transfer?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
        $data = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($data);
    }

    /**
     * 在职继承 - 分配在职成员的客户
     * @param $suiteId
     * @param $corpId
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function externalContactTransferCustomer($suiteId, $corpId, $params)
    {
        $url      = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/transfer_customer?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
        $respJson = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($respJson);
    }

    /**
     * 在职继承 - 查询客户接替状态
     * @param $suiteId
     * @param $corpId
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function externalContactTransferResult($suiteId, $corpId, $params)
    {
        $url      = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/transfer_result?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
        $respJson = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($respJson);
    }

    /**
     * 在职继承 - 分配在职成员的客户群
     * @param $suiteId
     * @param $corpId
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function externalContactGroupChatOnJobTransfer($suiteId, $corpId, $params)
    {
        $url      = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/groupchat/onjob_transfer?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
        $respJson = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($respJson);
    }

    /**
     * 通讯录搜索
     * @return array|mixed
     * @throws ErrException
     */
    public static function contactSearch($params)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/service/contact/search?' . http_build_query(['provider_access_token' => self::getProviderToken()]);
        $data = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($data);
    }

    /**
     * 获取企业标签库
     * @param string $suiteId 服务商ID
     * @param string $corpId 企业ID
     * @param array $params 请求参数
     * @return mixed
     * @throws ErrException
     */
    public static function getCorpTagList(string $suiteId, string $corpId, array $params = [])
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/get_corp_tag_list?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
        $data = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($data);
    }

    /**
     * 添加企业客户标签
     * @param string $suiteId 服务商ID
     * @param string $corpId 企业ID
     * @param array $params 请求参数
     * @return mixed
     * @throws ErrException
     */
    public static function addCorpTag(string $suiteId, string $corpId, array $params)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/add_corp_tag?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
        $data = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($data);
    }

    /**
     * 编辑企业客户标签
     * @param string $suiteId 服务商ID
     * @param string $corpId 企业ID
     * @param array $params 请求参数
     * @return mixed
     * @throws ErrException
     */
    public static function editCorpTag(string $suiteId, string $corpId, array $params)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/edit_corp_tag?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
        $data = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($data);
    }

    /**
     * 编辑企业客户标签
     * @param string $suiteId 服务商ID
     * @param string $corpId 企业ID
     * @param array $params 请求参数
     * @return mixed
     * @throws ErrException
     */
    public static function delCorpTag(string $suiteId, string $corpId, array $params)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/externalcontact/del_corp_tag?' . http_build_query(['access_token' => self::getSuiteCorpToken($suiteId, $corpId)]);
        $data = sendCurl($url, 'POST', json_encode($params, JSON_UNESCAPED_UNICODE));
        return self::resultData($data);
    }
}
