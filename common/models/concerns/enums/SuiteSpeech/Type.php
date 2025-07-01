<?php

namespace common\models\concerns\enums\SuiteSpeech;

use common\models\concerns\enums\Enum;

class Type extends Enum
{
    const ENUM = [
        1 => '文本',
        2 => '附件',
    ];

    /** @var int 文本 */
    const TEXT = 1;

    /** @var int 附件 */
    const ATTACHMENT = 2;
}