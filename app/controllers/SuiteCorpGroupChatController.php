<?php

namespace app\controllers;

use common\components\AppController;
use common\components\BaseController;
use common\services\SuiteCorpGroupChatMemberService;
use common\services\SuiteCorpGroupChatService;

class SuiteCorpGroupChatController extends BaseController
{

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionList()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpGroupChatService::items($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionDetails()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpGroupChatService::details($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionDetailsByChat()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpGroupChatService::detailsByChat($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionGetUserGroupCountById()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpGroupChatMemberService::getUserGroupCountById($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionGetOwner()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpGroupChatMemberService::getOwner($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionUpdateNotesByChatId()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpGroupChatService::updateNotesByChatId($params);
        return $this->responseSuccess($data);
    }

}