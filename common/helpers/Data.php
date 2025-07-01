<?php

namespace common\helpers;

use RuntimeException;
use Illuminate\Support\Str;

/**
 * 给使用类添加 getter 和 setter 方法
 * @example
 * class Obj {
 *     use Data;
 *     private $name;
 * }
 *
 * $obj = new Obj();
 *
 * # 设置属性:
 * $obj->setName('name');
 * # 或
 * $obj->name('name');
 *
 * # 获取属性
 * $obj->getName();
 * # 或
 * $obj->name();
 *
 */
trait Data
{
    public function __call($name, $arguments)
    {
        if (Str::startsWith($name, ['get', 'set'])) {
            $name = lcfirst(substr($name, 3));
        }

        if (property_exists($this, $name)) {
            if (empty($arguments)) {
                return $this->$name;
            } else {
                $this->$name = $arguments[0];
                return $this;
            }
        }

        throw new RuntimeException("Call to undefined method " . get_class($this) . "::$name()");
    }
}