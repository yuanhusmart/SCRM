<?php

namespace common\components;

use common\errors\Code;
use common\helpers\DataHelper;
use common\services\LoginService;
use common\errors\ErrException;

/**
 * Class AppController
 * @package common\components
 */
class AppController extends BaseController
{

    /**
     * @throws \Exception
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @param $action
     * @return bool
     * @throws ErrException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\web\BadRequestHttpException
     */
    public function beforeAction($action)
    {
        $accessToken = DataHelper::getAuthorizationTokenStr();
        if (empty($accessToken)) {
            throw new ErrException(Code::SIGN_PARAMS_ERROR);
        }
        try {
            if (!LoginService::verifyAccessToken($accessToken)) {
                throw new ErrException(Code::LOGIN_TOKEN_OVERDUE);
            }
            //判断用户路由权限
            if (LoginService::canRoutesPermissions($accessToken) === false) {
                //throw new ErrException(Code::ACCESS_DENIED);
            }
        } catch (\Exception $e) {
            throw $e;
        }
        return parent::beforeAction($action);
    }

}
