<?php

namespace app\controllers;

use common\components\AppController;
use common\errors\Code;
use common\models\SuiteSop;
use common\services\SuiteSopService;

class SuiteSopController extends AppController
{
    /**
     * sop列表
     * path: /suite-sop/info
     */
    public function actionInfo()
    {
        return $this->responseSuccess(
            SuiteSop::corp()
                ->select(['id','industry_no','sop_no','version'])
                ->andWhere(['industry_no' => $this->input('industry_no')])
                ->with(['items'])
                ->one()
        );
    }

    /**
     * 保存sop
     * path: /suite-sop/save
     */
    public function actionSave()
    {
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            SuiteSopService::save($this->params());
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            return $this->responseThrow($e);
        }
        return $this->responseSuccess();
    }

    /**
     * 全局复用
     * path: /suite-sop/reuse
     */
    public function actionReuse()
    {
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            SuiteSopService::reuse($this->params());
            $transaction->commit();
            return $this->responseSuccess();
        }catch (\Throwable $e){
            $transaction->rollBack();
            return $this->responseError(Code::BUSINESS_ABNORMAL, $e->getMessage());
        }
    }

}

