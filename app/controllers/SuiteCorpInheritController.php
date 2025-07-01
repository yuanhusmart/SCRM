<?php

namespace app\controllers;

use common\components\AppController;
use common\services\SuiteCorpInheritService;

class SuiteCorpInheritController extends AppController
{

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     * @throws \yii\db\Exception
     */
    public function actionCreate()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpInheritService::create($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     * @throws \yii\db\Exception
     */
    public function actionBatchCreate()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpInheritService::batchCreate($params);
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
        $data   = SuiteCorpInheritService::items($params);
        return $this->responseSuccess($data);
    }

}