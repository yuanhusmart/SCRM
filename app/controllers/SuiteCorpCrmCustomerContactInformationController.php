<?php

namespace app\controllers;

use common\components\AppController;
use common\errors\Code;
use common\services\crm\SuiteCorpCrmCustomerContactInformationService;
use Yii;

/**
 * 企业管理-CRM-客户联系人管理-联系方式管理
 */
class SuiteCorpCrmCustomerContactInformationController extends AppController
{
    /**
     * 新增联系方式
     * path: /suite-corp-crm-customer-contact-information/create
     * @return \yii\web\Response
     */
    public function actionCreate()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmCustomerContactInformationService::create($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

    /**
     * 保存联系方式
     * path: /suite-corp-crm-customer-contact-information/save
     * @return \yii\web\Response
     */
    public function actionSave()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmCustomerContactInformationService::save($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

    /**
     * 移除联系方式
     * path: /suite-corp-crm-customer-contact-information/remove
     * @return \yii\web\Response
     */
    public function actionRemove()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmCustomerContactInformationService::remove($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

    /**
     * 批量保存
     * path: /suite-corp-crm-customer-contact-information/batch-save
     * @return \yii\web\Response
     */
    public function actionBatchSave()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmCustomerContactInformationService::batchSave($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

}