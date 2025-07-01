<?php

namespace app\controllers;

use common\components\BaseController;
use common\errors\Code;
use common\errors\ErrException;
use common\services\LoginService;
use common\services\SuiteService;

/**
 * Class LoginController
 * @package app\controllers
 */
class LoginController extends BaseController
{

    /**
     * @return \yii\web\Response
     * @throws ErrException
     */
    public function actionGetPreAuthCode()
    {
        $params  = $this->getBodyParams();
        $suiteId = \Yii::$app->params["workWechat"]['suiteId'];
        if (!empty($params['suite_id'])) {
            $suiteId = $params['suite_id'];
        }
        $preAuthCode = SuiteService::getSuitePreAuthCode($suiteId);
        return $this->responseSuccess($preAuthCode);
    }

    /**
     * @return \yii\web\Response
     * @throws ErrException
     */
    public function actionGetTokenByCode()
    {
        $params    = $this->getBodyParams();
        $tokenData = LoginService::generateLoginDataByCode($params);
        $redis     = \Yii::$app->redis;
        $redisKey  = \Yii::$app->params["redisPrefix"] . 'token.' . $tokenData['token'];
        $redis->set($redisKey, json_encode($tokenData, true));
        $redis->expire($redisKey, 2 * 60 * 60); //2小时内有效
        return $this->responseSuccess($tokenData['token']);
    }

    /**
     * @return \yii\web\Response
     * @throws ErrException
     */
    public function actionGetDataByToken()
    {
        $params = $this->getBodyParams();
        if (empty($params['token'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $redisKey = \Yii::$app->params["redisPrefix"] . 'token.' . $params['token'];
        $data     = \Yii::$app->redis->get($redisKey);
        if (empty($data)) {
            throw new ErrException(Code::LOGIN_TOKEN_OVERDUE);
        }
        return $this->responseSuccess(json_decode($data, true));
    }

}