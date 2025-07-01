<?php

namespace app\controllers;

use common\components\AppController;
use common\components\BaseController;
use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpConfig;
use common\sdk\wework\Prpcrypt;
use common\services\Service;
use common\services\SuiteCorpConfigService;
use common\services\SuiteService;

class SuiteCorpConfigController extends BaseController
{

    /**
     * @return \yii\web\Response
     * @throws \yii\base\InvalidConfigException
     */
    public function actionList()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpConfigService::items($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionDetails()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpConfigService::details($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionUpdateStatus()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpConfigService::updateStatus($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionUpdateIsAutoAuth()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpConfigService::updateIsAutoAuth($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws ErrException
     * @throws \yii\db\Exception
     */
    public function actionUpdateTokens()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpConfigService::updateTokens($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionWebConfig()
    {
        $params = $this->getBodyParams();
        if (!$corpId = Service::getString($params, 'corp_id')) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        if (!$options = Service::getString($params, 'options')) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $msg  = urldecode($options);
        $corp = SuiteCorpConfig::find()->where(['corp_id' => $corpId])->one();
        if (!$corp) {
            return $this->responseError(Code::NOT_EXIST, '未找到企业信息');
        }
        $prpCrypt           = new Prpcrypt();
        $timestamp          = time();
        $nonceStr           = $prpCrypt->getRandomStr();
        $params['suite_id'] = $corp->suite_id;
        $params['config']   = [
            'corpid'    => $corpId,
            'agentid'   => intval($corp->suite_agent_id),
            'timestamp' => $timestamp,
            'nonceStr'  => $nonceStr,
            'signature' => sha1('jsapi_ticket=' . SuiteService::getTicket($corp->suite_id, $corp->corp_id) . '&' . 'noncestr=' . $nonceStr . '&' . 'timestamp=' . $timestamp . '&' . 'url=' . $msg),
        ];

        $nonceStr              = $prpCrypt->getRandomStr();
        $params['agentConfig'] = [
            'corpid'    => $corpId,
            'agentid'   => intval($corp->suite_agent_id),
            'timestamp' => $timestamp,
            'nonceStr'  => $nonceStr,
            'signature' => sha1('jsapi_ticket=' . SuiteService::getJsapiTicket($corp->suite_id, $corp->corp_id) . '&' . 'noncestr=' . $nonceStr . '&' . 'timestamp=' . $timestamp . '&' . 'url=' . $msg),
        ];
        return $this->responseSuccess($params);
    }

    /**
     * 获取数据与智能专区授权信息
     * @return \yii\web\Response
     * @throws ErrException
     */
    public function actionGetCorpAuthInfo()
    {
        $params = $this->getBodyParams();
        if (!$suiteId = Service::getString($params, 'suite_id')) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        if (!$corpId = Service::getString($params, 'corp_id')) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data = SuiteService::getCorpAuthInfo($suiteId, $corpId);
        return $this->responseSuccess($data);
    }

    /**
     * 修改套餐
     * path: /suite-corp-config/update-package
     */
    public function actionUpdatePackage()
    {
        $suiteId   = $this->input('suite_id');
        $corpId    = $this->input('corp_id');
        $packageId = $this->input('package_id');

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            /** @var SuiteCorpConfig $corp */
            $corp = SuiteCorpConfig::find()->where(['suite_id' => $suiteId, 'corp_id' => $corpId])->one();

            if (!$corp) {
                return $this->responseError(Code::NOT_EXIST, '未找到企业信息');
            }

            $corp->package_id = $packageId;
            $corp->save();

            // 需要处理角色的权限
            $corp->revisePackageRole();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            return $this->responseThrow($e);
        }

        return $this->responseSuccess();
    }
}