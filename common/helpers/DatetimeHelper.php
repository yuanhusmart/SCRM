<?php

namespace common\helpers;

use common\errors\Code;
use common\errors\ErrException;

/**
 * Class DatetimeHelper
 * @package common\helpers
 */
class DatetimeHelper
{
    const WEEK_ARRAY = array("日", "一", "二", "三", "四", "五", "六");

    /**
     * @param int $reduce
     * @return int
     * @throws \Exception
     */
    public static function reduceMonth($reduce)
    {
        if ($reduce >= 12) {
            throw new ErrException(Code::DATA_ERROR, '月份必须小于12');
        }
        $year  = date('Y');
        $month = date('n');
        $diff  = $month - $reduce;
        if ($diff >= 1) {
            $month = $diff;
        } else {
            $month = 12 + $diff;
            $year--;
        }
        $month = str_pad((string)$month, 2, '0', STR_PAD_LEFT);
        return strtotime($year . '-' . $month . '-01 00:00:00');
    }

    /**
     * 计算相差N月
     * @Author Wcj
     * @email 1054487195@qq.com
     * @DateTime 2020/8/28 14:58
     * @param $start
     * @param $end
     * @return string
     */
    public static function diffMonth($start, $end)
    {
        [$startY, $startM] = explode('-', date('Y-m', $start));
        [$endY, $endM] = explode('-', date('Y-m', $end));
        return abs($startY - $endY) * 12 + $endM - $startM;
    }

    /**
     * 获取当月1号0点的时间戳
     * @Author Wcj
     * @email 1054487195@qq.com
     * @DateTime 2020/9/7 15:00
     * @return false|int
     */
    public static function currentMonthTimestamp()
    {
        return strtotime(date('Y-m'));
    }

    /**
     * 获取指定日期时间戳的00:00:00时间戳
     * @Author Wcj
     * @email 1054487195@qq.com
     * @DateTime 2020/9/7 15:00
     * @return false|int
     */
    public static function currentDayTimestamp($time = null)
    {
        $timestamp = strtotime(date('Y-m-d'));
        if ($time) {
            $timestamp = strtotime(date('Y-m-d', $time));
        }
        return $timestamp;
    }

    /**
     * 获取指定时间的首末
     * @Author Wcj
     * @email 1054487195@qq.com
     * @DateTime 2020/9/12 15:07
     * @param int $timestamp
     * @param string $format
     * @return array
     */
    public static function timeStartEnd(int $timestamp = 0, string $format = 'ymd')
    {
        if (!$timestamp) {
            $timestamp = self::nowTime();
        }
        $first     = $last = '';
        $formatOne = 'Y-m-01';
        switch ($format) {
            case 'd': // 获取指定月的首日和末日
                $first = '01';
                $last  = date('d', strtotime(date($formatOne, $timestamp) . ' +1 month -1 day'));
                break;
            case 'ym': // 获取指定年的首年月和末年月
                $first = date('Y', $timestamp) . '-01';
                $last  = date('Y-m', strtotime($first . '-01' . ' +1 year -1 day'));
                break;
            case 'md': // 获取指定月的首月日和末月日
                $first = date('m', $timestamp) . '-01';
                $last  = date('m-d', strtotime(date($formatOne, $timestamp) . ' +1 month -1 day'));
                break;
            case 'ymd': // 获取指定月的首年月日和末年月日
                $first = date($formatOne, $timestamp);
                $last  = date('Y-m-d', strtotime("${first} +1 month -1 day"));
                break;
            case 'des':
                /**
                 * (day-end-string)获取指定月的首年月日时分秒字符串和末年月日时分秒字符串
                 * eg: 2020-10-01 00:00:00 2020-10-31 23:59:59
                 */
                $first = date('Y-m-01 H:i:s', $timestamp);
                $last  = date('Y-m-d H:i:s', strtotime("${first} +1 month -1 seconds"));
                break;
            case 'det':
                /**
                 * (day-end-timestamp)获取指定月的首年月日时分秒时间戳和末年月日时分秒时间戳;
                 * eg: 1601481600 1604159999 (2020-10-01 00:00:00 2020-10-31 23:59:59)
                 */
                $tmp   = date($formatOne, $timestamp);
                $first = strtotime($tmp);
                $last  = strtotime($tmp . ' +1 month -1 seconds');
                break;
            default:
                break;
        }
        return [$first, $last];
    }

    /**
     * 当前时间
     * @Author Wcj
     * @email 1054487195@qq.com
     * @DateTime 2020/9/16 11:45
     * @return int|mixed
     */
    public static function nowTime()
    {
        return $_SERVER['REQUEST_TIME'] ?? time();
    }

    /**
     * 设置时区
     * @Author Wcj
     * @email 1054487195@qq.com
     * @DateTime 2020/10/7 14:24
     */
    public static function setDefaultTimezone(): void
    {
        date_default_timezone_set('Asia/Shanghai');
    }

    /**
     * @param      $msectime
     * 毫秒转日期时间格式
     * @param null $type 默认 日期事件  day=>日期  time=>时间
     *
     * @return string|string[]
     */
    public static function getMsecToMescdate($msectime, $type = null)
    {
        $msectime = $msectime * 0.001;
        if (strstr($msectime, '.')) {
            sprintf("%01.3f", $msectime);
            list($usec, $sec) = explode(".", $msectime);
            $sec = str_pad($sec, 3, "0", STR_PAD_RIGHT);
        } else {
            $usec = $msectime;
            $sec  = "000";
        }
        $date     = date("Y-m-d H:i:s.x", $usec);
        $mescdate = str_replace('x', $sec, $date);
        if ($type == 'day') $mescdate = date('Y-m-d', strtotime($mescdate));
        if ($type == 'time') $mescdate = date('H:i:s', strtotime($mescdate));
        return $mescdate;
    }


    /**
     * 获取指定年月的所有日期
     *
     * @param string $date date('Y-m')
     *
     * @return array
     */
    public static function getMonthDays($date = '')
    {
        $monthDays = [];
        if (!empty($date)) {
            $firstDay = date("{$date}-01");
        } else {
            $firstDay = date('Y-m-01');
        }
        $i       = 0;
        $lastDay = date('Y-m-d', strtotime("$firstDay +1 month -1 day"));
        while (date('Y-m-d', strtotime("$firstDay +$i days")) <= $lastDay) {
            $monthDays[] = [
                'day'     => (int)date('d', strtotime("$firstDay +$i days")),
                'date'    => date('Y-m-d', strtotime("$firstDay +$i days")),
                'week'    => '星期' . self::WEEK_ARRAY[date('w', strtotime("$firstDay +$i days"))],
                'week_id' => date('w', strtotime("$firstDay +$i days"))
            ];
            $i++;
        }
        return $monthDays;
    }

    /**
     * 求两个日期之间相差的天数
     * (针对1970年1月1日之后)
     *
     * @param string $day1
     * @param string $day2
     *
     * @return number
     */
    public static function diffBetweenTwoDays($day1, $day2)
    {
        $second1 = strtotime($day1);
        $second2 = strtotime($day2);

        if ($second1 < $second2) {
            $tmp     = $second2;
            $second2 = $second1;
            $second1 = $tmp;
        }
        return ($second1 - $second2) / 86400;
    }


    /**
     * 查询指定时间范围内的所有日期，月份，季度，年份
     *
     * @param $startDate   指定开始时间，Y-m-d格式
     * @param $endDate     指定结束时间，Y-m-d格式
     * @param $type        类型，day 天，month 月份，quarter 季度，year 年份
     * @return array
     */
    public static function getDateByInterval($startDate, $endDate, $type = 'day')
    {
        $startDate = date('Y-m-d', strtotime($startDate));
        $endDate   = date('Y-m-d', strtotime($endDate));

        $tempDate   = $startDate;
        $returnData = [];
        $i          = 0;
        if ($type == 'day') {    // 查询所有日期
            while (strtotime($tempDate) < strtotime($endDate)) {
                $tempDate     = date('Y-m-d', strtotime('+' . $i . ' day', strtotime($startDate)));
                $returnData[] = $tempDate;
                $i++;
            }
        } elseif ($type == 'month') {    // 查询所有月份以及开始结束时间
            while (strtotime($tempDate) < strtotime($endDate)) {
                $temp              = [];
                $month             = strtotime('+' . $i . ' month', strtotime($startDate));
                $temp['name']      = date('Y-m', $month);
                $temp['startDate'] = date('Y-m-01', $month);
                $temp['endDate']   = date('Y-m-t', $month);
                $tempDate          = $temp['endDate'];
                $returnData[]      = $temp;
                $i++;
            }
        } elseif ($type == 'quarter') {    // 查询所有季度以及开始结束时间
            while (strtotime($tempDate) < strtotime($endDate)) {
                $temp              = [];
                $quarter           = strtotime('+' . $i . ' month', strtotime($startDate));
                $q                 = ceil(date('n', $quarter) / 3);
                $temp['name']      = date('Y', $quarter) . '第' . $q . '季度';
                $temp['startDate'] = date('Y-m-01', mktime(0, 0, 0, $q * 3 - 3 + 1, 1, date('Y', $quarter)));
                $temp['endDate']   = date('Y-m-t', mktime(23, 59, 59, $q * 3, 1, date('Y', $quarter)));
                $tempDate          = $temp['endDate'];
                $returnData[]      = $temp;
                $i                 = $i + 3;
            }
        } elseif ($type == 'year') {    // 查询所有年份以及开始结束时间
            while (strtotime($tempDate) < strtotime($endDate)) {
                $temp              = [];
                $year              = strtotime('+' . $i . ' year', strtotime($startDate));
                $temp['name']      = date('Y', $year) . '年';
                $temp['startDate'] = date('Y-01-01', $year);
                $temp['endDate']   = date('Y-12-31', $year);
                $tempDate          = $temp['endDate'];
                $returnData[]      = $temp;
                $i++;
            }
        }
        return $returnData;
    }

    /**
     * PHP叠计算两个时间段是否有交集（边界重不算）
     *
     * @param string $beginTime1 开始时间1
     * @param string $endTime1 结束时间1
     * @param string $beginTime2 开始时间2
     * @param string $endTime2 结束时间2
     *
     * @return bool
     */
    public static function is_time_cross($beginTime1, $endTime1, $beginTime2, $endTime2)
    {
        $beginTime1 = strtotime($beginTime1);
        $endTime1   = strtotime($endTime1);
        $beginTime2 = strtotime($beginTime2);
        $endTime2   = strtotime($endTime2);

        $status = $beginTime2 - $beginTime1;
        if ($status > 0) {
            $status2 = $beginTime2 - $endTime1;
            if ($status2 >= 0) {
                return false;
            } else {
                return true;
            }
        } else {
            $status2 = $endTime2 - $beginTime1;
            if ($status2 > 0) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 时间戳转字符串
     * @Author Wcj
     * @email 1054487195@qq.com
     * @DateTime 2020/12/10 15:36
     * @param int $time
     * @param string $format
     * @return string
     */
    public static function timeToStr(int $time = 0, $format = 'Y-m-d H:i:s'): string
    {
        $str = date($format);
        if ($time && is_numeric($time)) {
            $str = date($format, $time);
        }
        return $str;
    }

    /**
     * 判断是否为时间戳
     */
    public static function isTimestamp($timestamp)
    {
        $timestamp = is_numeric($timestamp) ? intval($timestamp) : $timestamp;
        if (strtotime(date('Y-m-d H:i:s', intval($timestamp))) === $timestamp) {
            return $timestamp;
        }

        return false;
    }

    /**
     * 处理起始时间戳(作用于列表条件)
     * @param string $startTime 开始 Y-m-d
     * @param string $endTime 结束 Y-m-d
     * @author 龚德铭
     * date 21.1.19
     *
     */
    public static function calculationTimestamp($startTime, $endTime)
    {
        if (!$startTime || !$endTime) {
            return [0, 0];
        }

        $startTime = self::isTimestamp($startTime) ? (int)$startTime : strtotime($startTime);
        $endTime   = self::isTimestamp($endTime) ? (int)$endTime : strtotime($endTime);

        if ($startTime == $endTime) {
            $endTime = strtotime('+1 day', strtotime(date('Y-m-d', $startTime))) - 1;
        }

        if ($endTime < $startTime) {
            return [0, 0];
        }

        return [$startTime, $endTime];
    }

    /**
     * 计算指定月的首尾时间戳
     * @param string $date 2021-02
     * @param bool $whetherDawn 结束时间是否算至凌晨 即 2021-02-28 23：59：59
     * @author 龚德铭
     * date 21.2.26
     */
    public static function getMonthHeadTail($date = '', $whetherDawn = false)
    {
        if (!$date) return [0, 0];

        $head = strtotime(date('Y-m-01', strtotime($date)));

        $days = date('t', $head);
        $days = $whetherDawn ? $days : $days - 1;

        $tail = $head + ($days * 24 * 3600) - ($whetherDawn ? 1 : 0);

        return [$head, $tail];
    }

    /**
     * 计算时间戳相差的年月日
     * @param int $startTime
     * @param int $endTime
     * @author 龚德铭
     * date 21.4.14
     */
    public static function calculationSpecificDate($startTime, $endTime)
    {
        $month = self::diffMonth($startTime, $endTime);

        if (empty($month)) return ['y' => 0, 'm' => 0, 'd' => 0];

        $float = $month / 12;

        if (strpos('.', $float) === false) {
            $y = $float;
            $d = 0;
        } else {
            list($y, $d) = explode('.', $float);
        }

        $d = floatval('0.' . $d);

        return [
            'y' => (int)$y,
            'm' => (int)$month - 12 * $y,
            'd' => (int)floor($d * 30)
        ];
    }

    /**
     * 获取两个时间差的 年 月 日 时 分 秒
     * @param string $startTime
     * @param string $endTime
     * @author 龚德铭
     */
    public static function timeDiff($startTime, $endTime)
    {
        $startTime = date('Y-m-d H:i:s', $startTime);
        $endTime   = date('Y-m-d H:i:s', $endTime);

        $startDateTime = new \DateTime($startTime);
        $endDateTime   = new \DateTime($endTime);
        $interval      = $startDateTime->diff($endDateTime);
        $formatMap     = [
            'y'    => 'year',
            'm'    => 'month',
            'd'    => 'day',
            'h'    => 'hour',
            'i'    => 'minute',
            's'    => 'second',
            'days' => 'days',
        ];
        $returnData    = [];

        foreach ($formatMap as $key => $val) {
            $returnData[$val] = $interval->{$key};
        }

        return $returnData;
    }

    /**
     * 格式化excel中的时间
     * @param string|float $time 读取到的值
     */
    public static function getExcelTime($time)
    {
        if (!is_numeric($time)) return 0;
        $n = intval(($time - 25569) * 3600 * 24); //转换成1970年以来的秒数
        return gmdate('Y-m-d H:i:s', $n);//格式化时间,不是用date哦, 时区相差8小时的
    }

    /**
     * 获取目标日期对应的农历日期
     * @param int $time
     * @return bool|false|int
     * @author 龚德铭
     * @date 2021-06-01 17:15
     */
    public static function getLunarDate($time = 0)
    {
        $time = $time ? $time : time();

        $tempTime = self::isTimestamp($time);
        $time     = $tempTime ? date('Y-m-d', $time) : $time;
        return (new Lunar())->S2L($time);
    }
}