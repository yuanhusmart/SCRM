<?php

namespace app\controllers;


use common\components\BaseController;
use common\errors\Code;
use common\errors\ErrException;
use common\models\OtsSuiteWorkWechatChatData;
use common\models\SuiteCorpConfig;
use common\sdk\TableStoreChain;
use common\sdk\wework\Prpcrypt;
use common\services\OtsSuiteWorkWechatChatDataService;
use common\services\Service;
use common\services\SuiteProgramService;
use common\services\SuiteService;

/**
 * 接收 企业微信服务商 数据与智能专区 程序回调（接收事件服务器）
 */
class SuiteProgramController extends BaseController
{

    /**
     * 程序回调 - 接收事件 /suite-program/receive
     * @return mixed|void
     */
    public function actionReceive()
    {
        \Yii::warning('/suite-program/receive 指令回调URL:----------------');
        $getParams = $this->getQueryParams();
        \Yii::warning('/suite-program/receive GET:----------------' . json_encode($getParams, JSON_UNESCAPED_UNICODE));
        \Yii::warning('/suite-program/receive POST:----------------' . json_encode($this->getBodyParams(), JSON_UNESCAPED_UNICODE));
        $xml = $this->getBodyXmlParams();
        \Yii::warning('/suite-program/receive XML:----------------' . json_encode($xml, JSON_UNESCAPED_UNICODE));


        $echoStr = $getParams['echostr'] ?? "";
        if ($echoStr) {
            $msgSignature = $getParams['msg_signature'] ?? "";
            $timestamp    = $getParams['timestamp'] ?? "";
            $nonce        = $getParams['nonce'] ?? "";
            $msgDecrypt   = (new Prpcrypt())->getWorkWechatMsgDecrypt($echoStr);
            \Yii::warning('/suite-program/receive echoStr Decrypt  ----------------' . json_encode($msgDecrypt, JSON_UNESCAPED_UNICODE));
            if (Service::getWorkWechatDevMsgSignature($timestamp, $nonce, $echoStr) == $msgSignature) {
                \Yii::warning('/suite-program/receive MSG_SIGNATURE 相等');
            } else {
                \Yii::warning('/suite-program/receive MSG_SIGNATURE 不相等');
            }
            return $msgDecrypt['msg'];
        }

        echo 'success';
        exit();
    }

    /**
     * @return bool|string|\yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionDemo()
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
        $config = SuiteCorpConfig::findOne(15)->toArray();
        $data   = SuiteProgramService::executionSyncCallProgram($config['suite_id'], $config['corp_id'], SuiteProgramService::PROGRAM_CREATE_WW_MODEL_TASK, [
            "ability_id" => SuiteProgramService::PROGRAM_CONVERSATION_ANALYSIS,
            "kb_id"      => "kbDWvHQtGUm8zUk229MjiyuMyclNFPT6Hj",
            "msg_list"   => $msgList
        ]);
        return $this->responseSuccess($data);
    }

    /**
     * @return array|mixed
     * @throws \common\errors\ErrException
     */
    public function actionDemoGetResult()
    {
        $config = SuiteCorpConfig::findOne(15)->toArray();
        $data   = SuiteProgramService::executionSyncCallProgram($config['suite_id'], $config['corp_id'], SuiteProgramService::PROGRAM_GET_WW_MODEL_RESULT, [
            "jobid" => "efohF0IFmQZJsQngCHsaD78SCI3TTTzmGfKdnwljGXbi01AprGZM_0oL2F3fm7mm",
//            "jobid" => 'ppKwNWbL4wHWYYXFF5lqYyq1fdI_yCkM-0fVOh1IqhcSgWXdPSUCxFBnh6EFdrTu'
        ]);
        return $this->responseSuccess($data);
    }

    /**
     * 应用同步调用专区程序
     * @return bool|string
     * @throws \common\errors\ErrException
     */
    public static function specNotifyApp()
    {
        $config = SuiteCorpConfig::findOne(15);
        return SuiteProgramService::executionSyncCallProgram($config->suite_id, $config->corp_id, SuiteProgramService::PROGRAM_ABILITY_SPEC_NOTIFY_APP);
    }

    /**
     * 设置专区接收回调事件
     * @return bool|string|\yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionSetReceiveCallback()
    {
        $id = $this->request->get('id');
        if (empty($id)) {
            return $this->responseError(Code::NOT_EXIST, '错误');
        }
        $config = SuiteCorpConfig::findOne($id);
        $data   = SuiteProgramService::setReceiveCallback($config->suite_id, $config->corp_id);
        return $this->responseSuccess($data);
    }

    /**
     * 设置公钥
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionSetPublicKey()
    {
        $id = $this->request->get('id');
        if (empty($id)) {
            return $this->responseError(Code::NOT_EXIST, '错误');
        }
        $config = SuiteCorpConfig::findOne($id);
        if (empty($config)) {
            return $this->responseError(Code::NOT_EXIST, '错误');
        }
        $publicKeyVer = intval($this->request->get('public_key_ver'));
        $publicKeyVer = empty($publicKeyVer) ? 1 : $publicKeyVer;

        $params['public_key']     = \Yii::$app->params["workWechat"]['publicKey'];
        $params['public_key_ver'] = $publicKeyVer;
        $params                   = SuiteService::setPublicKey($config->suite_id, $config->corp_id, $publicKeyVer);
        return $this->responseSuccess($params);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionSetSuiteSessionInfo()
    {
        $params  = $this->getBodyParams();
        $suiteId = \Yii::$app->params["workWechat"]['suiteId'];
        if (!empty($params['suite_id'])) {
            $suiteId = $params['suite_id'];
        }
        $data = SuiteService::setSuiteSessionInfo($suiteId, 1);
        return $this->responseSuccess($data);
    }

    /**
     * @return array|mixed
     * @throws ErrException
     */
    public function actionSearchContactOrCustomer()
    {
        $params = $this->getBodyParams();
        if (empty($params['suite_id']) || empty($params['corp_id']) || empty($params['query_word'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $attributes = Service::includeKeys($params, ['query_word', 'query_user_type', 'limit', 'cursor']);
        $data       = SuiteProgramService::executionSyncCallProgram($params['suite_id'], $params['corp_id'], SuiteProgramService::PROGRAM_SEARCH_CONTACT_OR_CUSTOMER, $attributes);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws ErrException
     */
    public function actionProlongTry()
    {
        $id = $this->request->get('id');
        if (empty($id)) {
            return $this->responseError(Code::NOT_EXIST, '错误');
        }
        $config = SuiteCorpConfig::findOne($id);
        if (empty($config)) {
            return $this->responseError(Code::NOT_EXIST, '错误');
        }
        $data = SuiteService::prolongTry($config->suite_id, $config->corp_id, $config->suite_agent_id);
        return $this->responseSuccess($data);
    }

}