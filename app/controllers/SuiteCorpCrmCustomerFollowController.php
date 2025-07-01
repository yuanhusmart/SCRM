<?php

namespace app\controllers;

use common\components\AppController;
use common\errors\Code;
use common\services\crm\SuiteCorpCrmCustomerFollowService;
use Yii;
use yii\web\Response;

/**
 * 企业管理-CRM-客户管理-跟进记录
 */
class SuiteCorpCrmCustomerFollowController extends AppController
{
    /**
     * 添加跟进记录
     * path: /suite-corp-crm-customer-follow/create
     * @return Response
     */
    public function actionCreate()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $id = SuiteCorpCrmCustomerFollowService::create($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess(['id' => $id]);
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

    /**
     * 跟进内容列表
     * path: /suite-corp-crm-customer-follow/index
     * @return Response
     */
    public function actionIndex()
    {
        return $this->responseSuccess(SuiteCorpCrmCustomerFollowService::index($this->getBodyParams()));
    }

}