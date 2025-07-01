<?php

namespace common\services\workWechat;

class ErrCode
{
    const MAP = [
        790033 => '名字中不能包含不可见字符'
    ];


    public static function message($code)
    {
        return self::MAP[$code] ?? '企微请求错误';
    }
}