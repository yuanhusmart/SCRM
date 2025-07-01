<?php

namespace app\controllers;

use common\components\AppController;
use common\components\BaseController;
use common\services\SuiteCorpSessionsService;

class SuiteCorpSessionsController extends BaseController
{

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionList()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpSessionsService::items($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionExternalContactList()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpSessionsService::externalContactItems($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionSearchMsg()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpSessionsService::searchMsg($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionSearchChat()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpSessionsService::searchChat($params);
        return $this->responseSuccess($data);
    }
}