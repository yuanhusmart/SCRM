<?php

namespace app\transformers;

use common\helpers\Format;
use Illuminate\Support\Arr;

class Helper
{
    /**
     * 格式化时间显示
     * @param array $data 单条数据
     * @param array $keys
     * @return array
     */
    public static function timeFormat(&$data, $keys = [])
    {
        foreach ($keys as $key) {
            if (Arr::has($data, $key)) {
                $time = Arr::get($data, $key);
                Arr::set($data, $key, Format::dateTime($time));
            }
        }

        return $data;
    }
}