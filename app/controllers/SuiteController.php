<?php

namespace app\controllers;

use common\components\BaseController;
use common\sdk\wework\Prpcrypt;
use common\services\SuiteService;

/**
 * 接收 企业微信服务商 指令回调
 */
class SuiteController extends BaseController
{

    /**
     * 数据回调URL : 用于接收托管企业微信应用的用户消息
     * @return null
     */
    public function actionDataCallback()
    {
        return $this->HandlingCallbacks();
    }

    /**
     * 指令回调URL : 系统将会把此应用的授权变更事件以及ticket参数推送给此URL
     * @return null
     */
    public function actionInstructionCallback()
    {
        return $this->HandlingCallbacks();
    }

    /**
     * 系统事件接收    /suite/system-event
     * @return mixed|void
     */
    public function actionSystemEvent()
    {
        return $this->HandlingCallbacks();
    }

    /**
     * 登陆授权接收指令回调URL    /suite/login-auth
     * @return mixed|void|\yii\web\Response
     */
    public function actionLoginAuth()
    {
        return $this->HandlingCallbacks();
    }

    /**
     * 统一处理GET、POST回调
     * @return mixed|void
     */
    private function HandlingCallbacks()
    {
        $logStr = sprintf('回调路由：%s，', $this->action->id);
        \Yii::warning("$logStr 开始");
        /*
         * POST请求接收xml数据格式
         * https://developer.work.weixin.qq.com/document/path/91116#32-%E6%94%AF%E6%8C%81http-post%E8%AF%B7%E6%B1%82%E6%8E%A5%E6%94%B6%E4%B8%9A%E5%8A%A1%E6%95%B0%E6%8D%AE
         */
        $xml = $this->getBodyXmlParams();
        if (!empty($xml['Encrypt'])) {
            $data = (new Prpcrypt())->getWorkWechatMsgDecrypt($xml['Encrypt']);
            \Yii::warning("$logStr 入参解密" . json_encode($data, JSON_UNESCAPED_UNICODE));
            SuiteService::eventCallback($data['msg'], $xml['AgentID'] ?? '');
        }

        /*
         * Http Get请求验证URL有效性
         * https://developer.work.weixin.qq.com/document/path/91116#31-%E6%94%AF%E6%8C%81http-get%E8%AF%B7%E6%B1%82%E9%AA%8C%E8%AF%81url%E6%9C%89%E6%95%88%E6%80%A7
         */
        $getParams = $this->getQueryParams();
        \Yii::warning("$logStr GET参数" . json_encode($getParams, JSON_UNESCAPED_UNICODE));
        $echoStr = $getParams['echostr'] ?? "";
        if ($echoStr) {
            \Yii::warning("$logStr GET加密EchoStr参数:" . json_encode($getParams, JSON_UNESCAPED_UNICODE));
            $msgDecrypt = (new Prpcrypt())->getWorkWechatMsgDecrypt($echoStr);
            \Yii::warning("$logStr GET解密参数:" . json_encode($msgDecrypt, JSON_UNESCAPED_UNICODE));
            return $msgDecrypt['msg'];
        }
        echo 'success';
        exit();
    }
}