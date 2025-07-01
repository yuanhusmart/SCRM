<?php

namespace common\models\concerns\enums\SuiteChatSetting;

use common\models\concerns\enums\Enum;

class Key extends Enum
{
    const ENUM = [
        'chat_log_sync_interval'      => '聊天记录同步周期',
        'internal_employee_exemption' => '内部员工免 分析/统计',
        'employee_follow_up_count'    => '员工跟进客户人数',
        'proactive_communication'     => '主动沟通',
        'passive_communication'       => '被动沟通',
    ];
}

