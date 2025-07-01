<?php

namespace app\controllers;


use common\components\AppController;
use common\services\SuiteCorpConfigAdminListService;

class SuiteCorpConfigAdminListController extends AppController
{

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionList()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpConfigAdminListService::items($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     * @throws \yii\db\Exception
     */
    public function actionUpdate()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpConfigAdminListService::eventChangeAuth(['SuiteId' => $params['suite_id'], 'AuthCorpId' => $params['corp_id']]);
        return $this->responseSuccess($data);
    }

}