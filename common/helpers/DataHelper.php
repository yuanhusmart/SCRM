<?php

namespace common\helpers;

/**
 * Class DataHelper
 * @package common\helpers
 */
class DataHelper
{
    const ANALYSIS_PREFIX = '$$$';

    /**
     * @return string
     */
    public static function getAuthorizationTokenStr()
    {
        $authHeader = \Yii::$app->request->headers->get('authorization');
        if ($authHeader !== null && preg_match('/^' . 'Bearer' . '\s+(.*?)$/', $authHeader, $matches)) {
            $accessToken = $matches[1];
        }
        return $accessToken ?? '';
    }

    /**
     * @param string $data
     * @return bool
     */
    public static function isPhone($data)
    {
        return (is_string($data) || is_int($data)) && preg_match('/^1[3-9]\d{9}$/', $data);
    }

    /**
     * 返回指定月份的天数
     * @param int $year
     * @param int $month
     * @return int
     */
    public static function getMonthDay($year, $month)
    {
        if ($month == 2) {
            return self::isLeapYear($year) ? 29 : 28;
        } elseif (in_array($month, [1, 3, 5, 7, 8, 10, 12])) {
            return 31;
        } elseif (in_array($month, [4, 6, 9, 11])) {
            return 30;
        } else {
            return 0;
        }
    }

    /**
     * 判断是否闰年
     * @param bool $year
     * @return bool
     */
    public static function isLeapYear($year)
    {
        return ($year % 4 == 0 && $year % 100 != 0) || $year % 400 == 0;
    }

    /**
     * @param int $time1 time1 必须大于等于 time2
     * @param int $time2
     * @return string
     */
    public static function getDiffDatetime($time1, $time2)
    {
        if (!$time2 || $time2 >= $time1) {
            return '';
        }
        $now_year        = intval(date('Y', $time1));
        $now_month       = intval(date('m', $time1));
        $now_day         = intval(date('d', $time1));
        $induction_year  = intval(date('Y', $time2));
        $induction_month = intval(date('m', $time2));
        $induction_day   = intval(date('d', $time2));
        if ($now_day >= $induction_day) {
            $day = $now_day - $induction_day;
        } else {
            if ($now_month == 1) {
                $now_month = 12;
                $now_year--;
            } else {
                $now_month--;
            }
            $day = $now_day + (DataHelper::getMonthDay($induction_year, $induction_month) - $induction_day);
        }
        if ($now_month >= $induction_month) {
            $month = $now_month - $induction_month;
        } else {
            $now_year--;
            $month = $now_month + (12 - $induction_month);
        }
        $year = $now_year - $induction_year;
        return sprintf('%d年%d月%d天', $year, $month, $day);
    }

    /**
     * @param $birthday_time
     * @param $date_time
     * @return int
     */
    public static function getAge(int $birthday_time, int $date_time)
    {
        $age = 0;
        if ($birthday_time && $date_time) {
            $birthday = date("Y-m-d", $birthday_time);
            $date     = date("Y-m-d", $date_time);
            list($year, $mouth, $day) = explode("-", $birthday);
            list($now_year, $now_mouth, $now_day) = explode("-", $date);
            $age = $now_year - $year;
            if ($now_mouth > $mouth || ($now_mouth == $mouth && $now_day > $day)) {
                $age += 1;
            }
        }

        return $age;
    }

    /**
     * 输出
     * @param array|string|int|bool|null $data
     * @param array|string|int|bool|null $default
     * @author 龚德铭
     * date 21.1.16
     */
    public static function outPut(&$data, $default = '')
    {
        if (!isset($data) || $data === '') {
            return $default;
        }

        return $data;
    }

    /**
     * 获取当前域名
     */
    public static function getDomain()
    {
        return isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
    }

    /**
     * 按目标排序
     * @param $data
     * @param $target
     * @return array
     * 龚德铭
     * 2021/8/11 14:42
     */
    public static function sortByTarget($data, $target)
    {
        $return = [];

        foreach ($target as $tv) {
            if (isset($data[$tv])) {
                $return[] = $data[$tv];
            }
        }

        return $return;
    }

    /**
     * 判断是否为JSON数据
     * @param $data
     * @return bool
     * 龚德铭
     * 2022/3/26 14:59
     */
    public static function isJson($data)
    {
        json_decode($data);
        return json_last_error() == JSON_ERROR_NONE;
    }

    /**
     * 判断是否为base64
     * @param $base
     * @return false|int
     * 龚德铭
     * 2022/3/26 15:49
     */
    public static function isBase64($base)
    {
        if (self::is_utf8(base64_decode($base)) && base64_decode($base) != '') {
            return true;
        }
        return false;
    }

    /**
     * 加密
     * @param $data
     * @return string
     * 龚德铭
     * 2022/7/14 18:48
     */
    public static function encryption($data)
    {
        return self::ANALYSIS_PREFIX . base64_encode(json_encode($data));
    }

    /**
     * 解析
     * @param $data
     * 龚德铭
     * 2022/3/26 15:51
     */
    public static function analysis($data)
    {
        $strpos = strpos($data, self::ANALYSIS_PREFIX);
        /** 如果是以 dbsea 开头的视为加密 */
        if ($strpos === 0) {
            $data = str_replace(self::ANALYSIS_PREFIX, '', $data);
        } else {
            return $data;
        }

        $isBase = self::isBase64($data);
        if (!$isBase) {
            return self::isJson($data) ? json_decode($data, true) : $data;
        }

        $data = base64_decode($data);
        if (self::isJson($data)) {
            $data = json_decode($data);
        }

        return $data;
    }

    /**
     * 判断否为UTF-8编码
     *
     * @param $str
     * @return bool
     */
    public static function is_utf8($str)
    {
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($str[$i]);
            if ($c > 128) {
                if (($c > 247)) {
                    return false;
                } elseif ($c > 239) {
                    $bytes = 4;
                } elseif ($c > 223) {
                    $bytes = 3;
                } elseif ($c > 191) {
                    $bytes = 2;
                } else {
                    return false;
                }
                if (($i + $bytes) > $len) {
                    return false;
                }
                while ($bytes > 1) {
                    $i++;
                    $b = ord($str[$i]);
                    if ($b < 128 || $b > 191) {
                        return false;
                    }
                    $bytes--;
                }
            }
        }
        return true;
    }

    /**
     * 打印一行
     * @param mixed $message
     */
    public static function consoleLog($message)
    {
        if (!is_string($message)) {
            $message = json_encode($message, JSON_UNESCAPED_UNICODE);
        }
        echo '[' . self::getMicroDatetime() . '] ' . $message . PHP_EOL;
    }

    /**
     * 返回带有毫秒的日期时间
     * @return string
     */
    public static function getMicroDatetime()
    {
        $result    = date('Y-m-d H:i:s.');
        $microtime = strval(round(microtime(true), 4));
        $microtime = explode('.', $microtime);
        if (is_array($microtime) && count($microtime) == 2) {
            $microtime = $microtime[1];
        } else {
            $microtime = '0';
        }
        $microtime = str_pad($microtime, 4, '0');
        return $result . $microtime;
    }
}