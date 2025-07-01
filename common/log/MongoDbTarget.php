<?php

namespace common\log;

use common\models\DataCenterYiiLog;
use yii\helpers\VarDumper;

/**
 * Class MongoDbTarget
 * @package common\log
 */
class MongoDbTarget extends \yii\log\DbTarget
{

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function export() {
        foreach ($this->messages as $message) {
            list($text, $level, $category, $timestamp) = $message;
            if (!$text) {
                continue;
            }
            if (!is_string($text)) {
                // exceptions may not be serializable if in the call stack somewhere is a Closure
                if ($text instanceof \Throwable || $text instanceof \Exception) {
                    $text = (string)$text;
                } else {
                    $text = VarDumper::export($text);
                }
            }


            // 命令行事件不记录
            $content = '';
            if (\Yii::$app->id != "console") {
                $remote_host = \Yii::$app->request->getRemoteHost();
                $host        = \Yii::$app->request->hostInfo;
                $route       = \Yii::$app->request->getUrl();
                $content     = '客户端:' . $remote_host . '服务端:' . $host . '路由:' . $route;
            }

            DataCenterYiiLog::create([
                'level'    => intval($level),
                'category' => $category,
                'log_time' => $timestamp,
                'datetime' => $this->formatDatetime($timestamp),
                'prefix'   => $this->getMessagePrefix($message),
                'message'  => $content.$text
            ]);
        }
    }

    /**
     * @param $timestamp
     * @return string
     */
    public function formatDatetime($timestamp) {
        $timestamp = explode('.', strval($timestamp));
        $time      = intval($timestamp[0]);
        return date('Y-m-d H:i:s', $time) . (isset($timestamp[1]) ? '.' . $timestamp[1] : '');
    }

}