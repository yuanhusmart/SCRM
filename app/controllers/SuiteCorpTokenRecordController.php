<?php

namespace app\controllers;

use common\components\AppController;
use common\components\BaseController;
use common\services\SuiteCorpTokenRecordService;

class SuiteCorpTokenRecordController extends BaseController
{

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionCorpList()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpTokenRecordService::corpItems($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionList()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpTokenRecordService::items($params);
        return $this->responseSuccess($data);
    }

}