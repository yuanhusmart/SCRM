<?php

namespace app\controllers;

use common\components\AppController;
use common\errors\Code;
use common\services\crm\SuiteCorpCrmCustomerContactService;
use Yii;

/**
 * 企业管理-CRM-客户联系人管理
 */
class SuiteCorpCrmCustomerContactController extends AppController
{
    /**
     * 验证通过手机号码验证查询联系人列表
     * path: /suite-corp-crm-customer-contact/verify
     * @return \yii\web\Response
     */
    public function actionVerify()
    {
        $params = $this->getBodyParams();
        $contactNumbers = isset($params['contact_numbers']) ? $params['contact_numbers'] : [];
        return $this->responseSuccess(SuiteCorpCrmCustomerContactService::verifyExistsByParam($contactNumbers));
    }

    /**
     * 添加联系人
     * path: /suite-corp-crm-customer-contact/create
     * @return \yii\web\Response
     */
    public function actionCreate()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmCustomerContactService::create($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

    /**
     * 联系人列表
     * path: /suite-corp-crm-customer-contact/index
     * @return void|\yii\web\Response
     */
    public function actionIndex()
    {
        $list = SuiteCorpCrmCustomerContactService::index($this->getBodyParams());
        return $this->responseSuccess($list);
    }

    /**
     * 联系人列表追加数据
     * path: /suite-corp-crm-customer-contact/index-append
     * @return \yii\web\Response
     */
    public function actionIndexAppend()
    {
        return $this->responseSuccess(SuiteCorpCrmCustomerContactService::indexAppend($this->getBodyParams()));
    }

    /**
     * 联系人详情
     * path: /suite-corp-crm-customer-contact/info
     * @return \yii\web\Response
     */
    public function actionInfo()
    {
        return $this->responseSuccess(SuiteCorpCrmCustomerContactService::info($this->getBodyParams()));
    }

    /**
     * 删除联系人
     * path: /suite-corp-crm-customer-contact/remove
     * @return \yii\web\Response
     */
    public function actionRemove()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmCustomerContactService::remove($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

    /**
     * 修改客户联系人姓名
     * path: /suite-corp-crm-customer-contact/update-name
     * @return \yii\web\Response
     */
    public function actionUpdateName()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmCustomerContactService::updateName($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

    /**
     * 合并联系人
     * path: /suite-corp-crm-customer-contact/merge
     * @return \yii\web\Response
     */
    public function actionMerge()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmCustomerContactService::merge($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

}