<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpConfig;

/**
 * 企业微信 内部应用 上下游
 * TODO ： 暂不涉及上下游API
 */
class CorpGroupService extends Service
{

    const SXY_CORP_ID = '';

    const SXY_APP_AGENT_ID = '';

    const SXY_APP_SECRET = '';

    const SXY_CHAIN_ID = '';



    /**
     * @return bool|string
     * @throws ErrException
     */
    public static function getCorpGroupAccessToken()
    {
        $redisKey     = \Yii::$app->params["redisPrefix"] . 'sxy.corp.token.up';
        $redis        = \Yii::$app->redis;
        $tokenJsonStr = $redis->get($redisKey);
        if (empty($tokenJsonStr)) {
            $url          = 'https://qyapi.weixin.qq.com/cgi-bin/gettoken?' . http_build_query(['corpid' => self::SXY_CORP_ID, 'corpsecret' => self::SXY_APP_SECRET]);
            $tokenJsonStr = sendCurl($url, 'GET');
            $redis->set($redisKey, $tokenJsonStr);
            $redis->expire($redisKey, 1 * 60 * 60); //1小时内有效
        }
        \Yii::warning('上下游企业 上游获取 AccessToken:' . $tokenJsonStr);
        $respJson = json_decode($tokenJsonStr, true);
        return $respJson['access_token'];
    }

    /**
     * 获取下级/下游企业的access_token
     * @param $corpId
     * @return mixed
     * @throws ErrException
     */
    public static function getCorpGroupCorpAccessToken($corpId, $agentId)
    {
        $redisKey     = \Yii::$app->params["redisPrefix"] . 'sxy.corp.token.down.' . $corpId;
        $redis        = \Yii::$app->redis;
        $tokenJsonStr = $redis->get($redisKey);
        if (empty($tokenJsonStr)) {
            $url          = 'https://qyapi.weixin.qq.com/cgi-bin/corpgroup/corp/gettoken?' . http_build_query(['access_token' => self::getCorpGroupAccessToken()]);
            $tokenJsonStr = sendCurl($url, 'POST', json_encode([
                'corpid'        => $corpId,
                'business_type' => 1,
                'agentid'       => $agentId
            ], JSON_UNESCAPED_UNICODE));
            $redis->set($redisKey, $tokenJsonStr);
            $redis->expire($redisKey, 1 * 60 * 60); //1小时内有效
        }
        \Yii::warning('上下游企业 下游获取 AccessToken:' . $tokenJsonStr);
        $respJson = json_decode($tokenJsonStr, true);
        return $respJson['access_token'];
    }

    /**
     * 获取上下游列表
     * @return array|mixed
     * @throws ErrException
     */
    public static function getChainList()
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/corpgroup/corp/get_chain_list?' . http_build_query(['access_token' => self::getCorpGroupAccessToken()]);
        $data = sendCurl($url, 'GET');
        \Yii::warning('上下游企业 获取上下游列表:' . $data);
        $data = json_decode($data, true);
        return empty($data['chains']) ? [] : $data['chains'];
    }


    /**
     * 获取上下游通讯录分组
     * @return array|mixed
     * @throws ErrException
     */
    public static function getChainGroup()
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/corpgroup/corp/get_chain_group?' . http_build_query(['access_token' => self::getCorpGroupAccessToken()]);
        $data = sendCurl($url, 'POST', json_encode(['chain_id' => self::SXY_CHAIN_ID, 'groupid' => 1], JSON_UNESCAPED_UNICODE));
        \Yii::warning('上下游企业 获取上下游通讯录分组:' . $data);
        $data = json_decode($data, true);
        return empty($data['groups']) ? [] : $data['groups'];
    }

    /**
     * 获取企业上下游通讯录下的企业信息
     * @return array|mixed
     * @throws ErrException
     */
    public static function getChainCorpInfo()
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/corpgroup/corp/get_chain_corpinfo?' . http_build_query(['access_token' => self::getCorpGroupAccessToken()]);
        $data = sendCurl($url, 'POST', json_encode([
            'chain_id' => self::SXY_CHAIN_ID,
            'corpid'   => 'wp7sqIDQAArdGQlUWOkC3Fm4puo9ZWnQ',
        ], JSON_UNESCAPED_UNICODE));
        \Yii::warning('上下游企业 获取企业上下游通讯录下的企业信息:' . $data);
        return json_decode($data, true);
    }

    /**
     * 获取企业上下游通讯录分组下的企业详情列表
     *
     * [corpid=wpCDGGCAAAz9sT-pZFyFW9Dpla49qLww][corp_name=四川易鱼企业管理有限公司]
     * [corpid=wpCDGGCAAA9pxFCzNPTQI4BH0ABUXsQA][corp_name=十权法律]
     * [corpid=wpCDGGCAAA0yH3yPuHgHyvTUHpel3zVg][corp_name=鱼爪智云]
     * [corpid=wpCDGGCAAAsG4bfXAp8V1jdRckN4gx-A][corp_name=四川鱼爪文化传媒有限公司]
     * [corpid=wpCDGGCAAApbQ5hAeHgkAYxL2xlpo-iw][corp_name=四川鱼爪知识产权代理有限公司]
     * [corpid=wpCDGGCAAA3kDPh3t-GmBqWAUEWcPoKQ][corp_name=四川汉聪科技有限公司]
     * [corpid=wpCDGGCAAA1N4I5DuWZpkDtY4nRVGdfw][corp_name=麦创智汇知识产权代理有限公司]
     * [corpid=wpCDGGCAAAmTRXKKBgZlY0IBiyF-Q4Ow][corp_name=鱼爪网（成都）企业管理咨询]
     * [corpid=wpCDGGCAAALIw4lm0zvYgptLhGJ87EXA][corp_name=鱼爪网平台]
     * [corpid=wpCDGGCAAAmbDY8uKl_ZZ3UvdRPcLOow][corp_name=江雀网]
     * [corpid=wpCDGGCAAA29rdmEMZLTOWwMMwiOE84w][corp_name=鱼爪企业管理咨询有限公司]
     * [corpid=wpCDGGCAAAe0X8tRGCfDxBFhokkJAAcg][corp_name=成都微新文化传播有限公司]
     *
     * @return array|mixed
     * @throws ErrException
     */
    public static function getChainCorpInfoList()
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/corpgroup/corp/get_chain_corpinfo_list?' . http_build_query(['access_token' => self::getCorpGroupAccessToken()]);
        $data = sendCurl($url, 'POST', json_encode([
            'chain_id'     => self::SXY_CHAIN_ID,
            'groupid'      => 1,
            "need_pending" => false,
            "cursor"       => "",
            "limit"        => 0
        ], JSON_UNESCAPED_UNICODE));
        \Yii::warning('上下游企业 获取企业上下游通讯录分组下的企业详情列表:' . $data);
        return json_decode($data, true);
    }

    /**
     * 查询成员自定义id
     * @return array|mixed
     * @throws ErrException
     */
    public static function getChainUserCustomId($userid)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/corpgroup/corp/get_chain_user_custom_id?' . http_build_query(['access_token' => self::getCorpGroupAccessToken()]);
        $data = sendCurl($url, 'POST', json_encode([
            "chain_id" => self::SXY_CHAIN_ID,
            "corpid"   => self::SXY_CORP_ID,
            "userid"   => $userid
        ], JSON_UNESCAPED_UNICODE));
        \Yii::warning('上下游企业 查询成员自定义id:' . $data);
        $data = json_decode($data, true);
        return empty($data['user_custom_id']) ? [] : $data['user_custom_id'];
    }

    /**
     * 获取应用共享信息
     * @return array|mixed
     * @throws ErrException
     */
    public static function listAppShareInfo()
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/corpgroup/corp/list_app_share_info?' . http_build_query(['access_token' => self::getCorpGroupAccessToken()]);
        $data = sendCurl($url, 'POST', json_encode([
            "agentid"       => self::SXY_APP_AGENT_ID,
            "business_type" => 1,
            //"corpid"        => "wpCDGGCAAAe0X8tRGCfDxBFhokkJAAcg",
            "limit"         => 100,
        ], JSON_UNESCAPED_UNICODE));
        \Yii::warning('上下游企业 获取应用共享信息:' . $data);
        return json_decode($data, true);
    }


    /**
     * 读取成员
     * @return mixed
     * @throws ErrException
     */
    public static function userGet($userid)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/user/get?' . http_build_query([
//                'access_token' => self::getCorpGroupAccessToken(),
                'access_token' => self::getCorpGroupCorpAccessToken("wp7sqIDQAArdGQlUWOkC3Fm4puo9ZWnQ", "1000014"),
                'userid'       => $userid
            ]);
        $data = sendCurl($url, 'GET');
        \Yii::warning('上下游企业 读取成员:' . $data);
        return json_decode($data, true);
    }

    /**
     * 获取部门成员
     * @return mixed
     * @throws ErrException
     */
    public static function userSimpleList()
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/user/simplelist?' . http_build_query([
                'access_token'  => self::getCorpGroupCorpAccessToken('wpCDGGCAAAe0X8tRGCfDxBFhokkJAAcg'),
                'department_id' => 1
            ]);
        $data = sendCurl($url, 'GET');
        \Yii::warning('上下游企业 获取部门成员:' . $data);
        return json_decode($data, true);
    }

    /**
     * 获取子部门ID列表
     * @return mixed
     * @throws ErrException
     */
    public static function departmentSimpleList()
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/department/simplelist?' . http_build_query(['access_token' => self::getCorpGroupCorpAccessToken('wpCDGGCAAAe0X8tRGCfDxBFhokkJAAcg')]);
        $data = sendCurl($url, 'GET');
        \Yii::warning('上下游企业 获取子部门ID列表:' . $data);
        return json_decode($data, true);
    }

    /**
     * 获取指定的应用详情
     * @return mixed
     * @throws ErrException
     */
    public static function agentGet()
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/agent/get?' . http_build_query([
                'access_token' => self::getCorpGroupAccessToken(),
                'agentid'      => self::SXY_APP_AGENT_ID
            ]);
        $data = sendCurl($url, 'GET');
        \Yii::warning('上下游企业 获取指定的应用详情:' . $data);
        return json_decode($data, true);
    }

    /**
     * 获取成员ID列表
     * TODO : 无接口访问权限
     * @return mixed
     * @throws ErrException
     */
    public static function userListId()
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/user/list_id?' . http_build_query(['access_token' => self::getCorpGroupCorpAccessToken('wpCDGGCAAAe0X8tRGCfDxBFhokkJAAcg')]);
        $data = sendCurl($url, 'POST', json_encode(['limit' => 10000], JSON_UNESCAPED_UNICODE));
        \Yii::warning('上下游企业 获取成员ID列表:' . $data);
        return json_decode($data, true);
    }


    /**
     * external_userid查询pending_id
     * @return mixed
     * @throws ErrException
     */
    public static function externalUseridToPendingId($externalUserid)
    {
        $url  = 'https://qyapi.weixin.qq.com/cgi-bin/corpgroup/batch/external_userid_to_pending_id?' . http_build_query(['access_token' => self::getCorpGroupAccessToken()]);
        $data = sendCurl($url, 'POST', json_encode(['external_userid' => $externalUserid], JSON_UNESCAPED_UNICODE));
        \Yii::warning('上下游企业 external_userid查询pending_id:' . $data);
        return json_decode($data, true);
    }

}
