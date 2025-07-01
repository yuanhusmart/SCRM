<?php

namespace app\controllers;

use common\errors\Code;
use common\errors\ErrException;
use common\components\BaseController;
use Yii;
use yii\base\Application;
use yii\base\Event;
use yii\web\HttpException;

/**
 * Class IndexController
 * @package app\controllers
 */
class IndexController extends BaseController
{

    /**
     * 异常处理
     * @return \yii\web\Response
     */
    public function actionError()
    {
        $code      = 0;
        $message   = '系统内部错误';
        $exception = Yii::$app->errorHandler->exception;
        if ($exception) {
            $code    = $exception->getCode();
            $message = ErrException::getFriendlyMessage($exception);
        }
        if (!$code) {
            $code = Code::EXCEPTION;
        }
        $status = 500;
        if ($exception instanceof ErrException) {
            $status = $exception->getStatus();
        } elseif ($exception instanceof HttpException) {
            $status = $exception->statusCode;
            if ($status == 403) {
                $code = Code::NO_PERMISSION;
            } elseif ($status == 404) {
                $code = Code::NOT_EXIST;
            }
        }
        return $this->responseError($code, $message, $status);
    }

}