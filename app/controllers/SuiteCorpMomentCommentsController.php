<?php

namespace app\controllers;

use common\components\AppController;
use common\services\SuiteCorpMomentCommentsService;

class SuiteCorpMomentCommentsController extends AppController
{

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionList()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpMomentCommentsService::items($params);
        return $this->responseSuccess($data);
    }
}