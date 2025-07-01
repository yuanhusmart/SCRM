<?php
namespace common\helpers;

use Yii;

/**
 * Class DebugHelper
 * @package common\helpers
 */
class DebugHelper
{

    /**
     * @return bool
     */
    public static function isDebug()
    {
        $debugs = ['f56f795d17b0c44897368eeeedef401f'];
        return isset($_GET['debug']) && in_array($_GET['debug'], $debugs);
    }

    /**
     * @param mixed $message
     */
    public static function log($message)
    {
        if(!self::isDebug()){
            return;
        }
        if(is_string($message)){
            $data = json_decode($message, true);
            if(is_array($data)){
                $data['lief_cycle_id'] = Yii::$app->instance->getLiefCycleId();
                $message = $data;
            }
        }elseif(is_array($message)){
            $message['lief_cycle_id'] = Yii::$app->instance->getLiefCycleId();
        }
        if(!is_string($message)){
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }
        Yii::info($message, __METHOD__);
    }

}