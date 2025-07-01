<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\AppAccount;

/**
 * Class AppAccountService
 * @package common\services
 */
class AppAccountService extends Service
{

    /**
     * @param int $appId 应用ID
     * @return string 应用TOKEN
     * @throws ErrException;
     */
    public static function getAppTokenByAppId($appId)
    {
        $account = AppAccount::find()->where(['app_id' => $appId, 'data_status' => AppAccount::DATA_STATUS_NORMAL])->asArray()->one();
        if (!$account) {
            throw new ErrException(Code::APP_DOES_NOT_EXIST);
        }
        return $account['app_token'];
    }

}
