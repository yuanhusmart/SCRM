<?php

namespace common\models\concerns\enums\SuiteWechatLoginLog;

use common\models\concerns\enums\Enum;

class Status extends Enum
{
    const ENUM = [
        1 => '登录',
        2 => '离线',
    ];

    /** @var int 登录 */
    const LOGIN = 1;

    /** @var int 离线 */
    const OFFLINE = 2;
}