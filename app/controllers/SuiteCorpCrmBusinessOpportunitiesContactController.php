<?php

namespace app\controllers;

use common\components\AppController;
use common\errors\Code;
use common\services\crm\SuiteCorpCrmBusinessOpportunitiesContactService;
use Yii;
use yii\web\Response;

/**
 * 企业管理-CRM-商机管理-商机联系人管理
 */
class SuiteCorpCrmBusinessOpportunitiesContactController extends AppController
{
    /**
     * 保存商机联系人
     * path: /suite-corp-crm-business-opportunities-contact/save
     * @return Response
     */
    public function actionSave()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmBusinessOpportunitiesContactService::save($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

    /**
     * 删除商机联系人
     * path: /suite-corp-crm-business-opportunities-contact/remove
     * @return Response
     */
    public function actionRemove()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmBusinessOpportunitiesContactService::remove($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

    /**
     * 设为主要联系人
     * path: /suite-corp-crm-business-opportunities-contact/set-main
     * @return Response
     */
    public function actionSetMain()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmBusinessOpportunitiesContactService::setMain($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

    /**
     * 商机联系人列表
     * path: /suite-corp-crm-business-opportunities-contact/index
     * @return Response
     */
    public function actionIndex()
    {
        return $this->responseSuccess(SuiteCorpCrmBusinessOpportunitiesContactService::index($this->getBodyParams()));
    }

}