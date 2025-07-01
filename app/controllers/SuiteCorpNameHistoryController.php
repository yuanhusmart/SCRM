<?php

namespace app\controllers;

use common\components\AppController;
use common\services\SuiteCorpNameHistoryService;

class SuiteCorpNameHistoryController extends AppController
{

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionCreate()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpNameHistoryService::create($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionList()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpNameHistoryService::items($params);
        return $this->responseSuccess($data);
    }
}