<?php

use common\components\Auth;
use common\log\Log;

if (!function_exists('makeCode')) {
    function makeCode($type = 1, $len = 6)
    {
        $words1  = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',];
        $words2  = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        $numbers = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];

        switch ($type) {
            case 1:
                $ss = $numbers;
            break;
            case 2:
                $ss = $words1;
            break;
            case 3:
                $ss = $words2;
            break;
            case 4:
                $ss = array_merge($words1, $words2, $numbers);
            break;
            default :
                $ss = $numbers;
        }

        if ($len > count($ss)) {
            return false;
        }

        $code = '';
        for ($i = 0; $i < $len; $i++) {
            $k    = mt_rand(0, count($ss) - 1);
            $code .= $ss[$k];
        }

        return $code;
    }
}

/**
 * 检测手机号
 *
 * @param string $mobile 手机号
 * @return boolean 是否手机号
 */
if (!function_exists('checkMobile')) {
    function checkMobile($mobile)
    {
        $is_mobile = preg_match('/^[1][3,4,5,6,7,8,9][0-9]{9}$/', $mobile) ? true : false;
        return $is_mobile;
    }
}

/**
 * 检测邮箱
 *
 * @param string $email 邮箱
 * @return boolean 是否邮箱
 */
if (!function_exists('checkEmail')) {
    function checkEmail($email)
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }
        return false;
    }
}

/**
 * 检测邮箱
 *
 * @param string $email 邮箱
 * @return boolean 是否邮箱
 */
if (!function_exists('checkIp')) {
    function checkIp($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }
        return false;
    }
}

/**
 * 判断文件是否存在，支持本地及远程文件
 * @param String $file 文件路径
 * @return Boolean
 */
if (!function_exists('checkFileExists')) {
    function checkFileExists($file)
    {
        if (strtolower(substr($file, 0, 4)) == 'http') {
            // 远程文件
            $header = get_headers($file, true);

            return isset($header[0]) && (strpos($header[0], '200') || strpos($header[0], '304'));
        } else {
            // 本地文件
            return file_exists($file);
        }
    }
}

/**
 * 验证身份号码格式
 * @param String $file 文件路径
 * @return Boolean
 */
if (!function_exists('isIdCard')) {
    function isIdCard($id)
    {
        $id        = strtoupper($id);
        $regx      = "/(^\d{15}$)|(^\d{17}([0-9]|X)$)/";
        $arr_split = array();
        if (!preg_match($regx, $id)) {
            return FALSE;
        }
        if (15 == strlen($id)) { //检查15位
            $regx = "/^(\d{6})+(\d{2})+(\d{2})+(\d{2})+(\d{3})$/";

            @preg_match($regx, $id, $arr_split);
            //检查生日日期是否正确
            $dtm_birth = "19" . $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];
            if (!strtotime($dtm_birth)) {
                return FALSE;
            } else {
                return TRUE;
            }
        } else {         //检查18位
            $regx = "/^(\d{6})+(\d{4})+(\d{2})+(\d{2})+(\d{3})([0-9]|X)$/";
            @preg_match($regx, $id, $arr_split);
            $dtm_birth = $arr_split[2] . '/' . $arr_split[3] . '/' . $arr_split[4];
            if (!strtotime($dtm_birth)) { //检查生日日期是否正确
                return FALSE;
            } else {
                //检验18位身份证的校验码是否正确。
                //校验位按照ISO 7064:1983.MOD 11-2的规定生成，X可以认为是数字10。
                $arr_int = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
                $arr_ch  = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
                $sign    = 0;
                for ($i = 0; $i < 17; $i++) {
                    $b    = (int)$id[$i];
                    $w    = $arr_int[$i];
                    $sign += $b * $w;
                }
                $n       = $sign % 11;
                $val_num = $arr_ch[$n];
                if ($val_num != substr($id, 17, 1)) {
                    return FALSE;
                } else {
                    return TRUE;
                }
            }
        }
    }
}

/*
 * 	curl
 * 入参
 * 	  url     请求路径
 * 	  type    POST || GET
 * 	  body    请求数据数组
 * 	  headers 请求头数组
 * 	  $timeout 超时时间
 * 	  $two_way 是否二次握手
 */
if (!function_exists('sendCurl')) {
    function sendCurl($url, $type = 'POST', $body = array(), $headers = array(), $timeout = 0)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        //curl超时设置 0 表示无限等待
        if ($timeout > 0) {
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        }
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}

/*
 * 字符串屏蔽
 */
if (!function_exists('replaceStr')) {
    function replaceStr($str)
    {
        $len = mb_strlen($str);
        if ($len < 2) {
            $return = $str;
        } elseif ($len >= 2 and $len < 6) {
            $return = mb_substr($str, 0, 1) . '****';
        } elseif ($len >= 6 and $len < 9) {
            $return = mb_substr($str, 0, 2) . '****' . mb_substr($str, -2);
        } else {
            $return = mb_substr($str, 0, 3) . '****' . mb_substr($str, -3);
        }
        return $return;
    }
}

/*
 * 获取重定向后的URL地址
 */
if (!function_exists('getRedirectUrl')) {
    function getRedirectUrl($url)
    {
        $header = get_headers($url, 1);
        if (strpos($header[0], '301') !== false || strpos($header[0], '302') !== false) {
            if (is_array($header['Location'])) {
                return $header['Location'][count($header['Location']) - 1];
            } else {
                return $header['Location'];
            }
        } else {
            return $url;
        }
    }
}

if (!function_exists('columnToIndex')) {
    function columnToIndex(string $column)
    {
        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S',
            'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL',
            'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'];
        return array_search($column, $columns);
    }
}
if (!function_exists('isSerialized')) {
    function isSerialized($data)
    {
        $data = trim($data);
        if ('N;' == $data)
            return true;
        if (!preg_match('/^([adObis]):/', $data, $badions))
            return false;
        switch ($badions[1]) {
            case 'a' :
            case 'O' :
            case 's' :
                if (preg_match("/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data))
                    return true;
            break;
            case 'b' :
            case 'i' :
            case 'd' :
                if (preg_match("/^{$badions[1]}:[0-9.E-]+;\$/", $data))
                    return true;
            break;
        }
        return false;
    }
}
/**
 * 判断是否是json数据
 */
if (!function_exists('isNotJson')) {
    function isNotJson($str)
    {
        return is_null(json_decode($str, true));
    }
}

/**
 * 文件转base64输出
 * @param $image_file
 * @return string
 */
if (!function_exists('fileToBase64')) {
    function fileToBase64($image_file)
    {
        $image_info   = getimagesize($image_file);
        $image_data   = file_get_contents($image_file);
        $base64_image = 'data:' . $image_info['mime'] . ';base64,' . base64_encode($image_data);
        return $base64_image;
    }
}
/**
 * 获取文件后缀
 */
if (!function_exists('getExtension')) {
    function getExtension($file)
    {
        return str_replace('.', '', strrchr($file, '.'));
    }
}

/**
 *重置KEY
 */
if (!function_exists('keyReset')) {
    function keyReset(&$ar)
    {
        if (!is_array($ar)) return;
        foreach ($ar as $k => &$v) {
            if (is_array($v)) keyReset($v);
            if ($k === 'child') $v = array_values($v);
        }
    }
}
if (!function_exists('toImagePath')) {
    function toImagePath($string, $domain = '')
    {
        $data = [];
        if ($string) {
            $invoice_file = explode(",", $string);
            $alioss       = new \common\concrete\AliOss();
            foreach ($invoice_file as $k => $value) {
                $url      = $domain == 'oss' ? $alioss->getUrl($value) : $value;
                $data[$k] = [
                    'name'     => $url,
                    'status'   => 'done',
                    'thumbUrl' => $url,
                    'type'     => 'images/png',
                    'uid'      => $k,
                    'url'      => $url
                ];
            }
        }
        return $data;
    }
}

/** 获取IP */
if (!function_exists('getIp')) {
    function getIp()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        if (isset($_SERVER['REDIRECT_CLIENTIP'])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER['REDIRECT_CLIENTIP'];
            return $_SERVER['REDIRECT_CLIENTIP'];
        }
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }
}
/** 数组字段过滤 */
if (!function_exists('arrayKeyFilter')) {
    function arrayKeyFilter($field, $needData)
    {
        $data = array_filter($needData, function ($v, $k) use ($field) {
            if (!in_array($k, $field)) {
                return false;
            }
            return true;

        }, ARRAY_FILTER_USE_BOTH);
        return $data;
    }
}

if (!function_exists('getDateByFloatValue')) {
    /**
     * EXCEL  中读取到的时间原型是 浮点型，现在要转成 格式化的标准时间格式
     *        返回的时间是 UTC 时间（世界协调时间，加上8小时就是北京时间）
     * @param float|int $dateValue Excel浮点型数值
     * @param int $calendar_type 设备类型 默认Windows 1900.Windows  1904.MAC
     * @return int 时间戳
     */
    function getDateByFloatValue($dateValue = 0, $calendar_type = 1900)
    {
        // Excel中的日期存储的是数值类型，计算的是从1900年1月1日到现在的数值
        if (1900 == $calendar_type) { // WINDOWS中EXCEL 日期是从1900年1月1日的基本日期
            $myBaseDate = 25569;      // php是从 1970-01-01 25569是到1900-01-01所相差的天数
            if ($dateValue < 60) {
                --$myBaseDate;
            }
        } else {// MAC中EXCEL日期是从1904年1月1日的基本日期(25569-24107 = 4*365 + 2) 其中2天是润年的时间差？
            $myBaseDate = 24107;
        }

        // 执行转换
        if ($dateValue >= 1) {
            $utcDays     = $dateValue - $myBaseDate;
            $returnValue = round($utcDays * 86400);
            if (($returnValue <= PHP_INT_MAX) && ($returnValue >= -PHP_INT_MAX)) {
                $returnValue = (integer)$returnValue;
            }
        } else {
            // 函数对浮点数进行四舍五入
            $hours       = round($dateValue * 24);
            $mins        = round($dateValue * 1440) - round($hours * 60);
            $secs        = round($dateValue * 86400) - round($hours * 3600) - round($mins * 60);
            $returnValue = (integer)gmmktime($hours, $mins, $secs);
        }

        return $returnValue;// 返回时间戳
    }

    if (!function_exists('isBankCard')) {
        /**
         * 校验银行卡号
         * @param $card
         * @return bool
         * @author 龚德铭
         * @date 2023/5/10 11:00
         */
        function isBankCard($card)
        {
            $len      = strlen($card);
            $all      = [];
            $sum_odd  = 0;
            $sum_even = 0;
            for ($i = 0; $i < $len; $i++) {
                $all[] = substr($card, $len - $i - 1, 1);
            }
            //all 里的偶数key都是我们要相加的奇数位
            for ($k = 0; $k < $len; $k++) {
                if ($k % 2 == 0) {
                    $sum_odd += $all[$k];
                } else {
                    //奇数key都是要相加的偶数和
                    if ($all[$k] * 2 >= 10) {
                        $sum_even += $all[$k] * 2 - 9;
                    } else {
                        $sum_even += $all[$k] * 2;
                    }
                }
            }
            $total = $sum_odd + $sum_even;
            if ($total % 10 == 0) {
                return true;
            } else {
                return false;
            }
        }
    }
}

if (!function_exists('input')) {
    /**
     * 获取请求参数
     * @param $key
     * @param $default
     * @return array|mixed
     */
    function input($key = null, $default = null)
    {
        $input  = file_get_contents('php://input');
        $params = json_decode($input, true);
        if (!is_array($params)) {
            $params = array();
        }

        if (is_null($key)) {
            return $params;
        }

        return $params[$key] ?? $default;
    }
}


if (!function_exists('logger')) {
    /**
     * 获取log实例
     * @return Log
     */
    function logger(): Log
    {
        return new Log();
    }
}


if (!function_exists('strEncode')) {
    /**
     * 万能脱敏
     * @param $str
     * @param int $start
     * @param int $end
     * @param int $num
     * @param string $char
     * @return string
     */
    function strEncode($str, int $start = 3, int $end = 4, int $num = 2, string $char = '*'): string
    {
        if (!$str) {
            return '';
        }
        //值的边界问题处理
        if ($start < 0 || $end < 0 || $num < 0) {
            return $char;
        }

        //支持脱敏格式的自定义
        $encode = str_repeat($char, $num);

        //当脱敏后字符串长度等于原字符串长度时，默认只保留第一个字符串
        $subCount = $start + $end;
        if (mb_strlen($str) == $subCount) {
            return mb_substr($str, 0, 1) . $encode;
        }

        //默认脱敏保留
        return mb_substr($str, 0, $start) . $encode . mb_substr($str, (0 - $end), $end);
    }
}

if (!function_exists('dictSortMd5')) {
    /**
     * 字典排序md5
     * @param array $params
     * @param string $sort
     * @return string
     */
    function dictSortMd5(array $params, string $sort = 'ASC'): string
    {
        if ($sort != 'ASC') {
            rsort($params, SORT_STRING); // 降序
        } else {
            sort($params, SORT_STRING); // 升序
        }
        return md5(implode('', $params));
    }
}

if (!function_exists('actionLogDesc')) {
    function actionLogDesc(string $desc)
    {
        \Yii::$app->params['actionLogDesc'] = $desc;
    }
}

if (!function_exists('auth')) {
    /**
     * 获取Auth实例
     * @return Auth
     */
    function auth()
    {
        return new Auth();
    }
}

if (!function_exists('snowflakeId')) {
    /**
     * 雪花id
     * @return string
     */
    function snowflakeId(): string
    {
        return \common\services\Service::getSnowflakeId();
    }
}


if (!function_exists('isDate')) {
    /**
     * 判断是否是日期
     * @param $value
     * @return bool
     */
    function isDate($value)
    {
        return strtotime($value) !== false;
    }
}

if (!function_exists('isUnixTimestamp')) {
    /**
     * 判断是否是时间戳
     * @param $timestamp
     * @return bool
     */
    function isUnixTimestamp($timestamp): bool
    {
        if (!is_int($timestamp)){
            return false;
        }
        return ($timestamp >= 0 && $timestamp <= 2147483647);
    }
}


if (!function_exists('showSql')) {
    /**
     * 显示所有执行的sql
     */
    function showSql()
    {
        $logs = \Yii::getLogger()->getProfiling(['yii\db\Command::query', 'yii\db\Command::execute']);
        foreach ($logs as $log) {
            if (
                isset($log['info'])
            ) {
                if (
                    strpos($log['info'], 'SHOW FULL') === false &&
                    strpos($log['info'], '_schema') === false
                ) {
//                    echo 'EXPLAIN '. $log['info'] . ";\n====================================\n";
                    echo 'EXPLAIN ' . $log['info'] . ";\n\n\n";
                }
            }
        }
        ddump('end');
        return;
    }
}
if (!function_exists('modelVar')) {
    function modelVar(string $name,string $value,array &$var_args)
    {
        if (!$value){
            //自定义变量内容不能为空
            $var_args[] = ['name' => $name, 'value' => '无'];
        }else{
            //自定义变量内容最大为128个utf字符
            mb_strlen($value, 'utf8') > 128 && $value = mb_substr($value, 0, 128, 'utf8');
            $var_args[] = ['name' => $name, 'value' => $value];
        }
    }
}
