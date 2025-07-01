<?php

namespace app\controllers;


use common\components\AppController;
use common\components\BaseController;
use common\services\SuiteCorpKeywordService;

class SuiteCorpKeywordController extends BaseController
{

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionList()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpKeywordService::items($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionCreate()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpKeywordService::create($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \Throwable
     * @throws \common\errors\ErrException
     * @throws \yii\db\StaleObjectException
     */
    public function actionDelete()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpKeywordService::delete($params);
        return $this->responseSuccess($data);
    }

}