<?php

namespace common\helpers;

/**
 * 字符串助手类
 * Class ArrayHelper
 * @package common\helpers
 */
class StringHelper extends \yii\helpers\StringHelper
{
    /**
     * 十进制整数转为大写字母组成的26进制字符串
     * @param int $int
     * @return string
     */
    public static function intToLetter($int)
    {
        if ($int == 0) {
            return '0';
        }
        $remainder = $int % 26;
        if ($remainder == 0) {
            $remainder = 26;
        }
        $number = ceil($int / 26) - 1;
        if ($number >= 1 && $number <= 26) {
            return chr($number + 64) . chr($remainder + 64);
        } elseif ($number > 26) {
            return self::intToLetter($number) . chr($remainder + 64);
        } else {
            return chr($remainder + 64);
        }
    }

    /**
     * 字符串转为十进制
     * @param string $letter 由大写字母组成的字符串
     * @return int
     */
    public static function letterToInt($letter)
    {
        $int    = 0;
        $length = strlen($letter);
        for ($i = 0; $i < $length; $i++) {
            $int += pow(26, $length - $i - 1) * (ord(substr($letter, $i, 1)) - 64);
        }
        return $int;
    }

    /**
     * 脱敏字符串
     * @Author Wcj
     * @email 1054487195@qq.com
     * @DateTime 2020/12/3 16:54
     * @param $string
     * @param int $start
     * @param int $length
     * @param string $re
     * @return string
     */
    public static function desensitize($string, $start = 0, $length = 0, $re = '*')
    {
        if (empty($string) || empty($length) || empty($re)) {
            return $string;
        }
        $end     = $start + $length;
        $strLen  = mb_strlen($string);
        $str_arr = [];
        for ($i = 0; $i < $strLen; $i++) {
            if ($i >= $start && $i < $end) {
                $str_arr[] = $re;
            } else {
                $str_arr[] = mb_substr($string, $i, 1);
            }

        }
        return implode('', $str_arr);
    }
}