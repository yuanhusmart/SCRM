<?php

namespace app\controllers;


use common\components\AppController;
use common\components\BaseController;
use common\services\SuiteCorpChatAgreeService;

class SuiteCorpChatAgreeController extends BaseController
{

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionList()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpChatAgreeService::items($params);
        return $this->responseSuccess($data);
    }

}