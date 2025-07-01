<?php

namespace common\helpers;

use Carbon\Carbon;
use Yii;

/**
 * 格式化数据
 */
class Format
{
    /**
     * 金钱展示格式
     * @return string
     */
    public static function money($number)
    {
        return sprintf('%.2f', $number);
    }

    /**
     * 转成浮点数
     * @return float
     */
    public static function float($number, $precision = 2)
    {
        return (float)sprintf("%.{$precision}f", $number);
    }

    /**
     * 百分比格式化
     */
    public static function percentage($number, $precision = 2)
    {
        return sprintf('%s%%', sprintf('%.' . $precision . 'f', $number * 100));
    }

    /**
     * 格式化员工显示, 格式: 姓名(工号)
     * @param array|obj $obj
     */
    public static function staff($staff, $jnumberIndex = 'jnumber', $nameIndex = 'name')
    {
        if (!$staff) {
            return '';
        }

        $name    = data_get($staff, $nameIndex, '');
        $jnumber = data_get($staff, $jnumberIndex, '');

        if ($jnumber) {
            $name .= "({$jnumber})";
        }

        return $name;
    }

    /**
     * 将给定时间转化为 datetime 格式字符串: Y-m-d H:i:s
     *
     * 会自动转化初始值为空字符, 如: null, 0, '1970-01-01 08:00:00'
     * @param \Carbon\Carbon|int|string $time
     * @return string
     */
    public static function dateTime($time)
    {
        if (is_null($time)) {
            return '';
        }

        // carbon 对象
        if ($time instanceof Carbon) {
            return $time->getTimestamp() > 0 ? $time->toDateTimeString() : '';
        }

        // 时间戳
        if (is_numeric($time)) {
            return $time > 0 ? date('Y-m-d H:i:s', $time) : '';
        }

        // 字符串
        return $time === '1970-01-01 08:00:00' || $time === '0000-00-00 00:00:00' ? '' : $time;
    }

    /**
     * 将入参转换为年月日
     * @return string
     */
    public static function date($time)
    {
        if (is_null($time)) {
            return '';
        }

        // carbon 对象
        if ($time instanceof Carbon) {
            return $time->getTimestamp() > 0 ? $time->toDateString() : '';
        }

        // 时间戳
        if (is_numeric($time)) {
            return $time > 0 ? date('Y-m-d', $time) : '';
        }

        if (is_string($time)) {
            $time = Carbon::parse($time)->toDateString();
        }

        // 字符串
        return $time === '1970-01-01' || $time === '0000-00-00' ? '' : $time;
    }

    public static function timestamp($date)
    {
        if (empty($date)) {
            return 0;
        }

        if (is_numeric($date)) {
            return $date;
        }

        return strtotime($date);
    }

    /**
     * 将数组的每个元素转换为 int 类型
     * @param array $arr
     * @return array
     */
    public static function toIntArray(array $arr)
    {
        return array_map('intval', $arr);
    }

    /**
     * 将数组的每个元素转换为 string 类型
     * @param array $arr
     * @return array
     */
    public static function toStringArray(array $arr)
    {
        return array_map('string', $arr);
    }

    /**
     * @param float $dividend 被除数
     * @param float $divisor 除数
     * @param int $scale 精度
     * @return float
     */
    public static function rate($dividend, $divisor, $scale = 4)
    {
        if (!(float)$divisor) {
            return 0;
        }

        return (float)bcdiv($dividend, $divisor, $scale);
    }

    /**
     * 环比比率
     * @param float $current 现期
     * @param float $base 基期
     * @return float
     */
    public static function cycleRate($current, $base, $scale = 4)
    {
        if (!(float)$base) {
            return 0;
        }

        // 计算环比
        return round(self::rate($current, $base, $scale) - 1, $scale);
    }

    /**
     * 字符串转数组
     * @param string $string 字符串
     * @param string $separator 分隔符
     */
    public static function stringToArray($string, $separator = ',')
    {
        if (is_array($string)) return $string;

        if (is_string($string) && str_contains($string, $separator)) {
            return explode($separator, $string);
        }

        return [$string];
    }

    /**
     * 获取 OSS path 添加默认前缀
     */
    public static function ossPath($path)
    {
        $root = Yii::$app->params['oss_domain'];

        return $root . '/' . trim($path, '/');
    }
}

