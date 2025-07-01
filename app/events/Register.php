<?php

namespace app\events;

/**
 * 事件注册
 *
 * 想要事件生效, 需要在 $events 中填写对应的类名进行统一注册
 */
class Register
{
    /**
     * @var array
     */
    public static $events = [
        \app\events\SuitePermission\Update::class
    ];

    public static function register()
    {
        foreach (static::$events as $event) {
            $event::register();
        }
    }
}