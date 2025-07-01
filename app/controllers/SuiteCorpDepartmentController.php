<?php

namespace app\controllers;

use common\components\AppController;
use common\services\SuiteCorpDepartmentService;
use common\services\SuiteService;

class SuiteCorpDepartmentController extends AppController
{

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionList()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpDepartmentService::items($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionTree()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpDepartmentService::tree($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionTreeAll()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpDepartmentService::treeAll($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionDetails()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpDepartmentService::details($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionContactSearch()
    {
        $params = $this->getBodyParams();
        $data   = SuiteService::contactSearch($params);
        return $this->responseSuccess($data);
    }

}