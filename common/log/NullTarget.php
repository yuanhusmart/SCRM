<?php

namespace common\log;

use yii\log\FileTarget;

/**
 * 不输出日志
 */
class NullTarget extends FileTarget
{
    public function export()
    {
    }
}