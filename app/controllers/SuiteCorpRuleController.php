<?php

namespace app\controllers;


use common\components\AppController;
use common\components\BaseController;
use common\services\SuiteCorpRuleService;

class SuiteCorpRuleController extends BaseController
{

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionCreate()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpRuleService::create($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionDetails()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpRuleService::details($params);
        return $this->responseSuccess($data);
    }

}