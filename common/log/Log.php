<?php

namespace common\log;

use common\helpers\Data;
use common\helpers\Maker;
use Yii;
use yii\log\Logger;

/**
 * @method self|string category($value = null)
 */
class Log
{
    use Maker, Data;

    public $category = 'application';

    private $map = [
        'info'    => Logger::LEVEL_INFO,
        'warning' => Logger::LEVEL_WARNING,
        'error'   => Logger::LEVEL_ERROR,
        'debug'   => Logger::LEVEL_TRACE,
    ];

    public function log($level, $message, array $context = [])
    {
        $context = json_encode($context, JSON_UNESCAPED_UNICODE);

        $level = $this->map[$level] ?? Logger::LEVEL_INFO;

        Yii::getLogger()->log($message . ' ' . $context, $level, $this->category);
    }

    // info
    public function info($message, array $context = [])
    {
        $this->log('info', $message, $context);
    }

    // warning
    public function warning($message, array $context = [])
    {
        $this->log('warning', $message, $context);
    }

    // error
    public function error($message, array $context = [])
    {
        $this->log('error', $message, $context);
    }

    // debug
    public function debug($message, array $context = [])
    {
        $this->log('debug', $message, $context);
    }
}