<?php

namespace common\log;

use yii\log\FileTarget;

class SqlTarget extends FileTarget
{
    public function formatMessage($message) {
        \Yii::warning(json_encode($message));

        list($sql, $time, $params) = [
            $message[0],
            round($message[1]['time'] * 1000, 2),
            json_encode($message[1]['params'])
        ];

        // 清理换行符和多余空格（核心修改）
        $sql = preg_replace('/\s+/', ' ', $sql); // 替换所有空白序列为单个空格
        $sql = trim($sql); // 去除首尾空格

        return sprintf(
            "[%s] SQL: %s | Time: %sms | Params: %s",
            date('Y-m-d H:i:s', $message[3]),
            $sql,
            $time,
            $params
        );
    }
}