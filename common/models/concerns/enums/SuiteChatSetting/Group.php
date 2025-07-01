<?php

namespace common\models\concerns\enums\SuiteChatSetting;

use common\models\concerns\enums\Enum;

class Group extends Enum
{

    const ENUM = [
        '1' => '聊天消息',
        '2' => '内部聊天',
        '3' => '跟进情况',
        '4' => '有效沟通',
    ];
}