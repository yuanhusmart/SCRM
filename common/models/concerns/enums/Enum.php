<?php

namespace common\models\concerns\enums;


abstract class Enum
{
    /**
     * 获取枚举
     * @param mixed $index 传值获取对应枚举, 不传获取整个枚举
     * @param string $default 缺省值
     * @return string|string[]
     */
    public static function enum($index = null, $default = '')
    {
        if (is_null($index)) {
            return static::ENUM;
        }

        return static::ENUM[$index] ?? $default;
    }

    /**
     * 键值对数组转为适合前端使用
     * @return array
     */
    public static function label()
    {
        $data = [];

        foreach (static::ENUM as $key => $value) {
            $data[] = [
                'key'   => $key,
                'value' => $value,
            ];
        }

        return $data;
    }
}