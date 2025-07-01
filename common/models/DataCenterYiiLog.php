<?php

namespace common\models;

use Yii;
use yii\mongodb\ActiveRecord;

/**
 * Class DataCenterYiiLog
 * @package common\models
 */
class DataCenterYiiLog extends ActiveRecord
{

    /**
     * @param $params
     * @return bool
     */
    public static function create($params)
    {
        $collection = Yii::$app->mongodb->getCollection(self::collectionName());
        $result = $collection->insert($params);
        if ($result == true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    public static function collectionName()
    {
        return 'data_center_yii_log';
    }

    /**
     * @return array
     */
    public function attributes()
    {
        return [
            '_id',
            'level',
            'category',
            'log_time',
            'datetime',
            'prefix',
            'message'
        ];
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->toArray();
    }

}
