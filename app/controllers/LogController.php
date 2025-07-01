<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class LogController extends Controller
{
    /**
     * 下载指定的日志文件
     * @param string $filename 日志文件名
     * @return \yii\web\Response
     * @throws NotFoundHttpException 如果文件不存在
     * @throws ForbiddenHttpException 如果文件名不合法
     */
    public function actionDownload($filePath)
    {
        $filePath = Yii::getAlias("@". $filePath);

        if (!file_exists($filePath) || !is_file($filePath)) {
            throw new NotFoundHttpException('请求的日志文件不存在');
        }


        return Yii::$app->response->sendFile($filePath);
    }
}