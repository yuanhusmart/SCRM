<?php

namespace app\controllers;


use common\components\AppController;
use common\services\SuiteCorpConfigPassInfoService;

class SuiteCorpConfigPassInfoController extends AppController
{

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionList()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpConfigPassInfoService::items($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionCreate()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpConfigPassInfoService::create($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionUpdateStatus()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpConfigPassInfoService::updateStatus($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionDelete()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpConfigPassInfoService::delete($params);
        return $this->responseSuccess($data);
    }

}