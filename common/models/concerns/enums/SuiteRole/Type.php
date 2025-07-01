<?php

namespace common\models\concerns\enums\SuiteRole;

use common\models\concerns\enums\Enum;

class Type extends Enum
{
    const ENUM = [
        1 => '基础',
        2 => '自定义',
    ];

    /** @var int 基础 */
    const BASIC = 1;

    /** @var int 自定义 */
    const CUSTOM = 2;
}