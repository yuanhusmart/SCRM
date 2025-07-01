<?php

namespace app\controllers;

use common\components\AppController;
use common\errors\Code;
use common\services\crm\SuiteCorpCrmCustomerRequirementTagService;
use Yii;
use yii\web\Response;

/**
 * 企业管理-CRM-客户管理-需求标签
 */
class SuiteCorpCrmCustomerRequirementTagController extends AppController
{
    /**
     * 保存需求标签
     * path: /suite-corp-crm-customer-requirement-tag/save
     * @return \yii\web\Response
     */
    public function actionSave()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmCustomerRequirementTagService::save($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

    /**
     * 删除需求标签
     * path: /suite-corp-crm-customer-requirement-tag/remove
     * @return Response
     */
    public function actionRemove()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmCustomerRequirementTagService::remove($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }
}