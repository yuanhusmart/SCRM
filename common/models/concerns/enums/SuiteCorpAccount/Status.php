<?php

namespace common\models\concerns\enums\SuiteCorpAccount;

use common\models\concerns\enums\Enum;

class Status extends Enum
{
    const ENUM = [
        1 => '正常',
        2 => '已禁用',
        3 => '未激活',
        4 => '离职',
    ];

    /** @var int 正常 */
    const ACTIVATED = 1;

    /** @var int 已禁用 */
    const DISABLED = 2;

    /** @var int 未激活 */
    const UNACTIVATED = 3;

    /** @var int 离职 */
    const RESIGNED = 4;
}