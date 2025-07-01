<?php

namespace app\controllers;

use common\components\BaseController;
use common\services\OtsSuiteWorkWechatChatDataService;

class WorkWechatChatDataController extends BaseController
{

    /**
     * @return \yii\web\Response
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     * @throws \common\errors\ErrException
     */
    public function actionList()
    {
        $params = $this->getBodyParams();
        $data   = OtsSuiteWorkWechatChatDataService::items($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     * @throws \common\errors\ErrException
     */
    public function actionStatistics()
    {
        $params = $this->getBodyParams();
        $data   = OtsSuiteWorkWechatChatDataService::statistics($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     * @throws \common\errors\ErrException
     */
    public function actionStatisticsBySession()
    {
        $params = $this->getBodyParams();
        $data   = OtsSuiteWorkWechatChatDataService::statisticsBySession($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     * @throws \common\errors\ErrException
     */
    public function actionGetMsg()
    {
        $getParams = $this->getBodyParams();
        $data      = OtsSuiteWorkWechatChatDataService::getMsgById($getParams);
        return $this->responseSuccess($data);
    }

}