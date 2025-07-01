<?php

namespace app\events;

use common\helpers\Data;
use common\helpers\Maker;

abstract class Event extends \yii\base\Event
{
    use Maker, Data;

    /**
     * @var string 事件名称
     * 常用名称: create, update, delete...
     */
    public static $eventName = 'default';

    /**
     * 事件监听者
     * @var array
     */
    public static $handlers = [];

    /**
     * 触发事件
     */
    public function fire()
    {
        static::trigger(static::class, static::$eventName, $this);
    }

    /**
     * 注册创建内部合同事件
     * 绑定相关事件处理器
     *
     * 事件监听多的话建议新建 Listener 类来进行处理
     *
     * @example
     * 1. 绑定本类上的方法:
     * self::on(self::class, self::$eventName, [self::class, 'handleSomething']);
     */
    public static function register()
    {
        foreach (static::$handlers as $handler) {
            static::on(static::class, static::$eventName, $handler);
        }
    }
}