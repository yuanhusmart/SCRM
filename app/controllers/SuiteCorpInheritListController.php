<?php

namespace app\controllers;

use common\components\AppController;
use common\services\SuiteCorpInheritListService;

class SuiteCorpInheritListController extends AppController
{

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionList()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpInheritListService::items($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionListByExternal()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpInheritListService::itemsByExternal($params);
        return $this->responseSuccess($data);
    }

}