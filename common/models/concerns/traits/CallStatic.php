<?php

namespace common\models\concerns\traits;

trait CallStatic
{
    public static function __callStatic($method, $arguments)
    {
        return (new static)->$method(...$arguments);
    }

    public function __call($method, $arguments)
    {
        return $this->$method(...$arguments);
    }
}