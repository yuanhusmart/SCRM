<?php

namespace app\controllers;

use common\components\BaseController;
use common\services\SuiteCorpFeFileService;

class SuiteCorpFeFileController extends BaseController
{

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionDetails()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpFeFileService::item($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionCreateOrUpdate()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpFeFileService::createOrUpdate($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionDelete()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpFeFileService::delete($params);
        return $this->responseSuccess($data);
    }

}