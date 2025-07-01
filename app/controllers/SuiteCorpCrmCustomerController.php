<?php

namespace app\controllers;

use common\components\AppController;
use common\errors\Code;
use common\services\crm\SuiteCorpCrmCustomerService;
use Yii;

/**
 * 企业管理-CRM-客户管理
 */
class SuiteCorpCrmCustomerController extends AppController
{
    /**
     * 添加客户
     * path: /suite-corp-crm-customer/create
     * @return \yii\web\Response
     */
    public function actionCreate()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $id = SuiteCorpCrmCustomerService::create($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess(['id' => $id]);
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

    /**
     * 批量添加客户
     * path: /suite-corp-crm-customer/batch-create
     * @return \yii\web\Response
     */
    public function actionBatchCreate()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmCustomerService::batchCreate($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

    /**
     * 客户列表
     * path: /suite-corp-crm-customer/index
     * @return \yii\web\Response
     */
    public function actionIndex()
    {
        return $this->responseSuccess(SuiteCorpCrmCustomerService::index($this->getBodyParams()));
    }

    /**
     * 客户列表追加数据
     * path: /suite-corp-crm-customer/index-append
     * @return \yii\web\Response
     */
    public function actionIndexAppend()
    {
        return $this->responseSuccess(SuiteCorpCrmCustomerService::indexAppend($this->getBodyParams()));
    }

    /**
     * 客户详情
     * path: /suite-corp-crm-customer/info
     * @return \yii\web\Response
     */
    public function actionInfo()
    {
        return $this->responseSuccess(SuiteCorpCrmCustomerService::info($this->getBodyParams()));
    }

    /**
     * 修改客户信息
     * path: /suite-corp-crm-customer/save
     * @return \yii\web\Response
     */
    public function actionSave()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmCustomerService::save($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

    /**
     * 修改客户关系人信息
     * path: /suite-corp-crm-customer/save-link
     * @return \yii\web\Response
     */
    public function actionSaveLink()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmCustomerService::saveLink($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

    /**
     * 客户名称唯一性校验
     * path: /suite-corp-crm-customer/unique-customer-name
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionUniqueCustomerName()
    {
        return $this->responseSuccess(SuiteCorpCrmCustomerService::uniqueCustomerName($this->getBodyParams()));
    }

    /**
     * 删除协作人
     * path: /suite-corp-crm-customer/remove-link
     * @return \yii\web\Response
     */
    public function actionRemoveLink()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmCustomerService::removeLink($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

    /**
     * 转交客户
     * path: /suite-corp-crm-customer/move
     * @return \yii\web\Response
     */
    public function actionMove()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmCustomerService::move($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

    /**
     * 删除客户
     * path: /suite-corp-crm-customer/remove
     * @return \yii\web\Response
     */
    public function actionRemove()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmCustomerService::remove($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

    /**
     * 查询客户关系人列表
     * path: /suite-corp-crm-customer/link-index
     * @return \yii\web\Response
     */
    public function actionLinkIndex()
    {
        return $this->responseSuccess(SuiteCorpCrmCustomerService::linkIndex($this->getBodyParams()));
    }
}