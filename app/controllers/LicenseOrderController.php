<?php

namespace app\controllers;

use common\components\AppController;
use common\errors\ErrException;
use common\services\SuiteCorpLicenseActiveInfoService;
use common\services\SuiteCorpLicenseOrderService;

class LicenseOrderController extends AppController
{

    /**
     * @return \yii\web\Response
     * @throws ErrException
     */
    public function actionPull()
    {
        $data = SuiteCorpLicenseOrderService::licenseOrderPull();
        return $this->responseSuccess($data);
    }


    /**
     * @return \yii\web\Response
     * @throws ErrException
     */
    public function actionUnionOrderPull()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpLicenseOrderService::unionOrderPull($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionList()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpLicenseOrderService::items($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws ErrException
     */
    public function actionCreateNewOrder()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpLicenseOrderService::createNewOrder($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws ErrException
     */
    public function actionSubmitNewOrderJob()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpLicenseOrderService::submitNewOrderJob($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws ErrException
     */
    public function actionCreateRenewOrderJob()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpLicenseOrderService::createRenewOrderJob($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws ErrException
     */
    public function actionActiveList()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpLicenseActiveInfoService::items($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws ErrException
     */
    public function actionUpdateIsAutoAuth()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpLicenseActiveInfoService::updateIsAutoAuth($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws ErrException
     */
    public function actionGetUseridActiveCodeExpireTime()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpLicenseActiveInfoService::getUseridActiveCodeExpireTime($params);
        return $this->responseSuccess($data);
    }


    /**
     * @return \yii\web\Response
     * @throws ErrException
     */
    public function actionBindActiveAccount()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpLicenseOrderService::bindActiveAccount($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws ErrException
     */
    public function actionBindBatchActiveAccount()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpLicenseOrderService::bindBatchActiveAccount($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws ErrException
     */
    public function actionBatchTransferLicense()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpLicenseOrderService::batchTransferLicense($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws ErrException
     */
    public function actionCancelOrder()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpLicenseOrderService::cancelOrder($params);
        return $this->responseSuccess($data);
    }
}