<?php

namespace console\controllers;

use common\errors\Code;
use common\errors\ErrException;
use common\models\DataCenterYiiLog;
use Exception;
use yii\console\Controller;

/**
 * Class LogController
 * @package console\controllers
 */
class LogController extends Controller
{

    public $log_start_time; // 日志开始时间
    public $log_end_time; // 日志结束时间

    public function options($actionID)
    {
        return [
            'log_start_time', // 日志开始时间
            'log_end_time' // 日志结束时间
        ];
    }

    /**
     * 清除一个月之前的错误日志 每天 01:00 执行
     * @throws Exception
     * php ./yii log/clean-log
     */
    public function actionCleanLog()
    {
        // 一个月前的时间
        $time = time() - 2592000;
        $res = DataCenterYiiLog::deleteAll(['<=', 'log_time', $time]);
        echo '清除时间 ' . date("Y-m-d H:i:s", $time) . ' 以前的数据条数：' . $res . PHP_EOL;
    }

    /**
     * 指定时间范围删除日志
     * @throws Exception
     * php ./yii log/clear-log
     */
    public function actionClearLog()
    {
        // 一个月前的时间
        $log_start_time = $this->log_start_time ?? '';
        $log_end_time = $this->log_end_time ?? time();
        if (empty($log_start_time)) {
            throw new ErrException(Code::DATA_ERROR, '日志开始时间不能为空!');
        }
        $res = DataCenterYiiLog::deleteAll(['between','log_time',(int)$log_start_time,(int)$log_end_time]);
        echo '清除时间 在' . date("Y-m-d H:i:s", $log_start_time) .'和'. date("Y-m-d H:i:s", $log_end_time) . '范围 以内的数据条数：' . $res . PHP_EOL;
        die;
    }
}