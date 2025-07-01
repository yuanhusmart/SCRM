<?php

namespace app\controllers;

use common\components\AppController;
use common\errors\Code;
use common\services\crm\SuiteCorpCrmCustomerContactTagService;
use Yii;

/**
 * 企业管理-CRM-客户联系人管理-标签管理
 */
class SuiteCorpCrmCustomerContactTagController extends AppController
{
    /**
     * 新增标签
     * path: /suite-corp-crm-customer-contact-tag/create
     * @return \yii\web\Response
     */
    public function actionCreate()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmCustomerContactTagService::create($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

    /**
     * 移除标签
     * path: /suite-corp-crm-customer-contact-tag/remove
     * @return \yii\web\Response
     */
    public function actionRemove()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmCustomerContactTagService::remove($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

}