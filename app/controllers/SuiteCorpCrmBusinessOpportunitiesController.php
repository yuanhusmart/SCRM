<?php

namespace app\controllers;

use common\components\AppController;
use common\errors\Code;
use common\errors\ErrException;
use common\services\crm\SuiteCorpCrmBusinessOpportunitiesService;
use Yii;
use yii\web\Response;

/**
 * 企业管理-CRM-商机管理
 */
class SuiteCorpCrmBusinessOpportunitiesController extends AppController
{
    /**
     * 添加商机
     * path: /suite-corp-crm-business-opportunities/create
     * @return \yii\web\Response
     */
    public function actionCreate()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $id = SuiteCorpCrmBusinessOpportunitiesService::create($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess(['id' => $id]);
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

    /**
     * 商机列表
     * path: /suite-corp-crm-business-opportunities/index
     * @return \yii\web\Response
     */
    public function actionIndex()
    {
        return $this->responseSuccess(SuiteCorpCrmBusinessOpportunitiesService::index($this->getBodyParams()));
    }

    /**
     * 商机详情
     * path: /suite-corp-crm-business-opportunities/info
     * @return Response
     * @throws ErrException
     */
    public function actionInfo()
    {
        return $this->responseSuccess(SuiteCorpCrmBusinessOpportunitiesService::info($this->getBodyParams()));
    }

    /**
     * 保存商机
     * path: /suite-corp-crm-business-opportunities/save
     * @return Response
     */
    public function actionSave()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmBusinessOpportunitiesService::save($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

    /**
     * 作废商机
     * path: /suite-corp-crm-business-opportunities/cancel
     * @return \yii\web\Response
     */
    public function actionCancel()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmBusinessOpportunitiesService::cancel($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

    /**
     * 转移商机
     * path: /suite-corp-crm-business-opportunities/move
     * @return Response
     */
    public function actionMove()
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            SuiteCorpCrmBusinessOpportunitiesService::move($this->getBodyParams());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

    /**
     * 根据商机编号获取最新会话分析结果
     * path: /suite-corp-crm-business-opportunities/session-analysis
     * @return Response
     */
    public function actionSessionAnalysis()
    {
        return $this->responseSuccess(SuiteCorpCrmBusinessOpportunitiesService::sessionAnalysis($this->getBodyParams()));
    }
}