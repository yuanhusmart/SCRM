<?php

namespace app\controllers;

use common\components\AppController;
use common\components\BaseController;
use common\services\SuiteCorpExternalContactService;

class SuiteCorpExternalContactController extends BaseController
{

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionList()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpExternalContactService::items($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionGetNameById()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpExternalContactService::getNameById($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionGetCountByUserid()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpExternalContactService::getExternalContactCountByUserid($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionInheritList()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpExternalContactService::inheritItems($params);
        return $this->responseSuccess($data);
    }

}