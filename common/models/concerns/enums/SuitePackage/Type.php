<?php

namespace common\models\concerns\enums\SuitePackage;

use common\models\concerns\enums\Enum;

class Type extends Enum
{
    const ENUM = [
        1 => '企业',
        2 => '服务商',
        3 => '试用版',
    ];

    /** @var int 企业 */
    const ENTERPRISE = 1;

    /** @var int 服务商 */
    const SERVICE_PROVIDER = 2;

    /** @var int 试用版 */
    const TRIAL = 3;
}

