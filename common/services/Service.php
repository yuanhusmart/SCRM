<?php

namespace common\services;

use common\concrete\Aes;
use common\errors\Code;
use common\errors\ErrException;
use common\helpers\ArrayHelper;
use Godruoyi\Snowflake\RedisSequenceResolver;
use Godruoyi\Snowflake\Snowflake;
use Yii;

/**
 * 基础服务和工具类
 * Class Service
 * @package common\services
 */
class Service
{

    /**
     * @var \Redis
     */
    protected static $redis;

    /**
     * 获取 雪花 ID
     * @return string
     */
    public static function getSnowflakeId($datacenterId = 0)
    {
        $workerId     = \Yii::$app->params['snowflake']['worker_id'];
        $dataCenterId = $datacenterId ?: \Yii::$app->params['snowflake']['server_id'];
        $snow         = new Snowflake($dataCenterId, $workerId);

        if (empty(self::$redis)) {
            self::$redis = new \Redis();
            self::$redis->connect(env('REDIS_HOST', '127.0.0.1'), env('REDIS_PORT', 6379), env('REDIS_DATABASE', 2));
            self::$redis->auth(env('REDIS_PASSWORD'));
        }
        $snow->setSequenceResolver(new RedisSequenceResolver(self::$redis));
        return $snow->id();
    }

    /**
     * 公有参数验证
     * @param array $params
     * @param bool $main
     * @return array
     * @throws ErrException
     */
    protected function condition(array $params, bool $main = false): array
    {
        try {
            $condition = [
                "project"     => $params["project"],
                "staff_style" => $params["staff_style"],
                "type"        => $params["type"],
            ];
            if ($main) {
                $condition["order_id"] = $params["order_id"];
            } else {
                $condition["order_brand_id"] = $params["order_brand_id"];
            }
            return $condition;
        } catch (\Throwable $throwable) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
    }

    /**
     * 返回分页信息
     * @param array $params
     * @param int $max_per_page
     * @return array
     */
    public static function getPageInfo($params, $max_per_page = 2000)
    {
        $page     = 1;
        $per_page = 30;
        if (is_array($params) && isset($params['page']) && is_numeric($params['page']) && $params['page'] >= 1) {
            $page = intval($params['page']);
        }
        if (is_array($params) && isset($params['per_page']) && is_numeric($params['per_page']) && $params['per_page'] >= 1) {
            if ($params['per_page'] >= $max_per_page) {
                $per_page = $max_per_page;
            } else {
                $per_page = intval($params['per_page']);
            }
        }
        if (\Yii::$app->id == 'console') {
            $per_page = empty($params['per_page']) ? $per_page : intval($params['per_page']);
        }
        return [$page, $per_page];
    }

    /**
     * @return string|null
     */
    public static function ip()
    {
        $ip = Service::getString($_SERVER, 'REMOTE_ADDR', '127.0.0.1');
        return $ip;
    }

    /**
     * 返回排序信息
     * @param array $params
     * @param array $fields
     * @param string|null $default_sort
     * @return array
     */
    public static function getOrderInfo($params, $fields, $default_sort = null)
    {
        $order_by = self::getString($params, 'order_by');
        $sort     = self::getString($params, 'sort');
        if ($sort) {
            $sort = strtoupper($sort);
        }
        $sort_map = [
            'ASC'  => SORT_ASC,
            'DESC' => SORT_DESC
        ];
        if (in_array($order_by, $fields) && isset($sort_map[$sort])) {
            $sort = $sort_map[$sort];
        } else {
            $order_by = null;
            $sort     = null;
        }
        if (!$order_by && !$sort && $fields && $default_sort) {
            $order_by = current($fields);
            $sort     = $default_sort;
        }
        return [$order_by, $sort];
    }

    /**
     * 获取参数中的 id
     * @param array $params
     * @return int|null
     */
    public static function getId($params)
    {
        return self::getInt($params, 'id');
    }

    /**
     * 获取参数中的 ids，数组
     * @param array $params
     * @param string $key
     * @return int[]
     */
    public static function getIds($params, $key)
    {
        if (isset($params[$key])) {
            return self::formatInts($params[$key]);
        } else {
            return [];
        }
    }

    /**
     * 格式化为整型数组，具有以下操作：
     *
     * 1、使用逗号分割字符串
     * 2、移除不是数字的元素
     * 3、统一转为整型
     * 4、移除重复的元素
     * 5、重置数组为索引数组
     * 6、可根据参数再返回逗号连接的字符串
     *
     * 范例：
     *
     * $value = '123,456,abc,789';
     * return [123,456,789];
     *
     * $value = [123, '456', '456', 'abc', 789];
     * return [123,456,789];
     *
     * @param mixed $value
     * @param bool $string 为 true 时，返回用逗号连接的多个整数字符串
     * @return int[]|string
     */
    public static function formatInts($value, $string = false)
    {
        $ints = [];
        if (is_array($value)) {
            $ints = $value;
        } elseif (is_numeric($value) || is_string($value)) {
            $ints = explode(',', $value);
        }
        foreach ($ints as $k => $id) {
            if (is_numeric($id)) {
                $ints[$k] = intval($id);
            } else {
                unset($ints[$k]);
            }
        }
        $ints = array_values(array_unique($ints));
        if ($string) {
            return implode(',', $ints);
        } else {
            return $ints;
        }
    }

    /**
     * 将方法 self::formatInts() 返回的数组元素转为字符串
     * @param mixed $value
     * @return string[]|int[]
     */
    public static function formatIntStrings($value)
    {
        $ints = self::formatInts($value);
        foreach ($ints as $k => $int) {
            $ints[$k] = strval($int);
        }
        return $ints;
    }

    /**
     * 从数组中获取一个字符串
     * @param array $params 参数
     * @param string $key 键名
     * @param string $value 默认值
     * @param bool $is_trim 是否清除首位的空白
     * @return string|null
     */
    public static function getString($params, $key, $value = '', $is_trim = true)
    {
        if (is_array($params) && isset($params[$key]) && (is_string($params[$key]) || is_numeric($params[$key]))) {
            $value = $is_trim ? trim($params[$key]) : $params[$key];
        }
        return $value;
    }

    /**
     * @param array $params
     * @param string $key
     * @param array $default
     * @return string[]
     */
    public static function getStringArray($params, $key, $default = [])
    {
        $value = $default;
        if (is_array($params) && isset($params[$key]) && is_array($params[$key])) {
            $value = $params[$key];
            foreach ($value as $k => $item) {
                if (!is_scalar($item)) {
                    unset($value[$k]);
                    continue;
                }
                $item = strval($item);
                if (strlen($item)) {
                    $value[$k] = $item;
                } else {
                    unset($value[$k]);
                }
            }
        }
        return $value;
    }

    /**
     * 从数组中获取一个整数
     * @param array $params 参数
     * @param string $key 键名
     * @return int|null 不存在时返回 null
     */
    public static function getInt($params, $key)
    {
        $value = null;
        if (is_array($params) && isset($params[$key]) && is_numeric($params[$key])) {
            $value = intval($params[$key]);
        }
        return $value;
    }

    /**
     * 从数组中获取一个浮点数
     * @Author Wcj
     * @email 1054487195@qq.com
     * @DateTime 2020/10/9 17:00
     * @param array $params 参数
     * @param string $key 键名
     * @return float|null 不存在时返回 0
     */
    public static function getFloat($params, $key)
    {
        $value = 0;
        if (is_array($params) && isset($params[$key]) && is_numeric($params[$key])) {
            $value = floatval($params[$key]);
        }
        return $value;
    }

    /**
     * 从数组中获取一个数组
     * @param array $params 参数
     * @param string $key 键名
     * @param array|mixed $default 默认值
     * @return array
     */
    public static function getArray($params, $key, $default = [])
    {
        $value = $default;
        if (is_array($params) && isset($params[$key]) && is_array($params[$key])) {
            $value = $params[$key];
        }
        return $value;
    }

    /**
     * 从数组中移除指定的几个元素
     * @param array $params
     * @param array|string $keys
     * @return array
     */
    public static function unsetKeys($params, $keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        foreach ($keys as $key) {
            if (isset($params[$key])) {
                unset($params[$key]);
            }
        }
        return $params;
    }

    /**
     * 从数组中取出指定的几个元素
     * @param array $params
     * @param array|string $keys
     * @param bool $clear_null
     * @return array
     */
    public static function includeKeys($params, $keys, $clear_null = true)
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        foreach ($params as $k => $value) {
            if (!in_array($k, $keys) || ($clear_null && $value === null)) {
                unset($params[$k]);
            }
        }
        return $params;
    }

    /**
     * 获取数组中的一个时间戳
     * @param array $params
     * @param string $key
     * @param bool $day_end
     * @return int
     */
    public static function getTimestamp($params, $key, $day_end = false)
    {
        $timestamp = 0;
        $formats   = ['Y-n', 'Y-m', 'Y-n-j', 'Y-m-d', 'Y-m-d H:i:s'];
        foreach ($formats as $format) {
            $pattern = '/^' . strtr($format, [
                    'Y' => '\d{4}',
                    'n' => '\d{1,2}',
                    'm' => '\d{2}',
                    'j' => '\d{1,2}',
                    'd' => '\d{2}',
                    'H' => '\d{2}',
                    'i' => '\d{2}',
                    's' => '\d{2}',
                    '-' => '\-'
                ]) . '$/';
            if (isset($params[$key]) && is_string($params[$key]) && preg_match($pattern, $params[$key])) {
                $time = strtotime($params[$key]);
                if (date($format, $time) == $params[$key]) {
                    $timestamp = $time;
                    break;
                }
            } elseif (isset($params[$key]) && is_numeric($params[$key])) {
                if (preg_match($pattern, date($format, $params[$key]))) {
                    $timestamp = $params[$key];
                }
            }
        }
        if ($timestamp && $day_end) {
            $timestamp = strtotime(date('Y-m-d 23:59:59', $timestamp));
        }
        return $timestamp;
    }

    /**
     * @param mixed $data
     * @return false|string
     */
    public static function jsonEncode($data)
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * @param array $params
     * @param array $keys
     * @return array|null
     */
    public static function getFields($params, $keys = ['fields', 'field', 'feild'])
    {
        if (!(is_array($params) && (isset($params['fields']) || isset($params['field']) || isset($params['feild'])))) {
            return null;
        }
        $fields = [];
        foreach ($keys as $key) {
            if (!isset($params[$key])) {
                continue;
            }
            if (is_array($params[$key])) {
                $fields = array_merge($fields, $params[$key]);
            } elseif (is_string($params[$key]) && $params[$key]) {
                $fields = array_merge($fields, explode(',', $params[$key]));
            }
        }
        $fields = array_unique($fields);
        foreach ($fields as $k => $field) {
            if (!($field && is_string($field))) {
                unset($fields[$k]);
            }
            if ($field == '*') {
                return null;
            }
        }
        return array_values($fields);
    }

    /**
     * @param array $data
     * @return array
     */
    public static function setKeys($data)
    {
        $result = [];
        foreach ($data as $value) {
            $result[(string)$value] = $value;
        }
        return $result;
    }

    /**
     * @param array $data
     * @return \stdClass
     */
    public static function stdClass($data)
    {
        $std = new \stdClass();
        foreach ($data as $key => $value) {
            $std->{$key} = $value;
        }
        return $std;
    }

    /**
     * @param $money
     * @return mixed
     */
    public static function getMoney($money)
    {
        if (is_numeric($money) && $money > 0) {
            return $money;
        }
        return null;
    }

    /**
     * 只保留字符串首尾字符，隐藏中间用*代替（两个字符时只显示第一个）
     * @param string $user_name 字符串
     * @param int $head 左侧保留位数
     * @param int $foot 右侧保留位数
     * @param int $length 中间替换*号数量
     * @return string 格式化后的姓名
     */
    public static function subStrCut($user_name, $head, $foot, $length = 0)
    {
        $strLen   = mb_strlen($user_name, 'utf-8');
        $firstStr = mb_substr($user_name, 0, $head, 'utf-8');
        $lastStr  = mb_substr($user_name, -$foot, $foot, 'utf-8');
        if ($length) {
            $replaceStr = '';
            for ($i = 1; $i <= $length; $i++) {
                $replaceStr .= '*';
            }
            $str = $firstStr . $replaceStr . $lastStr;
        } else {
            $str = $strLen <= ($head + $foot) ? $firstStr . str_repeat('*', mb_strlen($user_name, 'utf-8') - 1) : $firstStr . str_repeat("*", $strLen - ($head + $foot)) . $lastStr;
        }
        return $str;
    }

    /**
     * 获取参数及验证是否不存在，或是否在规定值范围参数，或自定按验证
     * @param array $params
     * @param array $keysAndRule ['msg'=>'', 'must'=>true, 'in_intArr'=>[]int, 'in_strArr'=>[]string, 'callback'=>false]
     * @return array
     * @throws ErrException
     */
    public static function includeKeysAndCheck(array $params, array $keysAndRule): array
    {
        $return     = [];
        $paramsKeys = array_keys($params);
        foreach ($keysAndRule as $key => $item) {
            if ($item) {//验证值
                if ($item['must'] ?? true) {   //是否必须验证有值 默认是
                    if (empty($params[$key])) {
                        throw new ErrException(Code::PARAMS_ERROR, ($item['msg'] ?? '') . '参数缺失');
                    }

                }
                if (!empty($params[$key])) {
                    if ($item['in_intArr'] ?? false) {
                        if (!in_array((int)$params[$key], $item['in_intArr'], true)) {
                            throw new ErrException(Code::PARAMS_ERROR, ($item['msg'] ?? '') . '参数错误');
                        }
                    }
                    if ($item['in_strArr'] ?? false) {
                        if (!in_array((string)$params[$key], $item['in_strArr'], true)) {
                            throw new ErrException(Code::PARAMS_ERROR, ($$item['msg'] ?? '') . '参数错误');
                        }
                    }
                    if ($item['callback'] ?? false) $item['callback']($params[$key]);
                }
            }
            if (in_array($key, $paramsKeys)) {
                $return[$key] = $params[$key];
            }
        }
        return $return;
    }

    /**
     * @param array $must
     * @param array $params
     * @param string $relation
     * @return bool
     */
    public static function whetherParamExist(array $must, array $params, string $relation = "&&")
    {
        $exist = false;
        foreach ($must as $item) {
            if ($relation == "&&") {
                if (isset($params[$item]) == false) {
                    return false;
                }
                continue;
            }
            if ($relation == "||") {
                if (isset($params[$item])) {
                    $exist = true;
                }
            }
        }
        if ($relation == "||" && $exist == false) {
            return false;
        }

        return true;
    }

    /**
     * @param array $must
     * @param array $params
     * @param array $keys
     * @return bool
     * @throws ErrException
     */
    public static function whetherParamExistByModel(array $must, array $params, $keys = [])
    {

        if (empty($keys)) {
            return true;
        }
        foreach ($must as $item) {
            if (isset($params[$item]) == false) {
                throw new ErrException(Code::PARAMS_ERROR, $keys[$item] . $item . '必填!');
            }
            continue;
        }

        return true;
    }

    /**
     * AES加密
     * @param string $appId
     * @param string $appSecret
     * @return string
     */
    public static function aes256($appId, $appSecret)
    {
        $aes = new Aes($appSecret);

        $string = json_encode([
            'random'    => mt_rand(1000, 9999),
            'appId'     => $appId,
            'timestamp' => time()
        ]);

        $en = $aes->encrypt($string);

        return $en;
    }

    /**
     * 解析ES返回数据
     * @param $data
     * @return array
     * 龚德铭
     * 2022/7/3 15:12
     */
    public static function analysisSearchResult($data)
    {
        $data  = is_string($data) ? json_decode($data, true) : $data;
        $total = ArrayHelper::getValue($data, 'hits.total.value', 0);

        $hits = ArrayHelper::getValue($data, 'hits.hits', []);
        $list = [];
        foreach ($hits as $hv) {
            $temp = ArrayHelper::getValue($hv, '_source');
            if (empty($temp)) {
                continue;
            }
            $list[] = $temp;
        }

        return [$list, $total];
    }

    /**
     * 解析ES返回数据(游标查询)
     * @param $data
     * @return array
     * 龚德铭
     * 2022/7/3 15:12
     */
    public static function analysisSearchResultExport($data)
    {
        $data     = is_string($data) ? json_decode($data) : $data;
        $total    = ArrayHelper::getValue($data, 'hits.total.value', 0);
        $scrollId = ArrayHelper::getValue($data, '_scroll_id');

        $hits = ArrayHelper::getValue($data, 'hits.hits', []);
        $list = [];
        foreach ($hits as $hv) {
            $temp = ArrayHelper::getValue($hv, '_source');
            if (empty($temp)) {
                continue;
            }
            $list[] = $temp;
        }

        return [$list, $total, $scrollId];
    }

    /**
     * 解析求和统计结果
     * @param $searchResult
     * @param $aggField
     * @return mixed|null
     * @author 龚德铭
     * @date 2023/9/11 9:38
     */
    public static function analysisAggregationsResult($searchResults, $aggField)
    {
        return ArrayHelper::getValue($searchResults, "aggregations.{$aggField}.value");
    }

    /**
     * 解析分组统计
     * @param $searchResult
     * @param $groupField
     * @return array
     * @author 龚德铭
     * @date 2023/9/11 16:08
     */
    public static function analysisGroupResult($searchResult, $groupField)
    {
        $return = [];
        if (empty($searchResult) || !is_array($searchResult)) {
            return $return;
        }

        $buckets = ArrayHelper::getValue($searchResult, "aggregations.{$groupField}.buckets", []);
        if (empty($buckets)) {
            return $return;
        }

        return array_column($buckets, 'doc_count', 'key');
    }

    /**
     * 保存在临时文件夹下
     * @return string
     * @throws \Exception
     */
    public static function getFileDir($params, $path)
    {
        if (!file_exists($path)) {
            mkdir($path, 0777);
        }
        chmod($path, 0777);
        if (!is_dir($path)) {
            throw new \Exception("mkdir {$path} failed");
        }
        return $path . $params->baseName . '.' . $params->extension;
    }

    /**
     * 删除文件
     * @param $dir
     * @return bool
     */
    public static function removeDir($dir)
    {
        if (is_dir($dir)) {
            $s = scandir($dir);
            foreach ($s as $i) {
                if ($i != "." && $i != "..") {
                    self::removeDir($dir . "/" . $i);
                }
            }
            @rmdir($dir);
        } elseif (is_file($dir)) {
            @unlink($dir);
        } else {
            return false;
        }
        return true;
    }

    /**
     * excel浮点数转时间
     * @param float $float
     * @return false|string
     */
    public static function excelFloatToTime(float $float)
    {
        $date_int = getDateByFloatValue($float);// 获取浮点型时间对应的时间戳（UTC时间）
        return gmdate('Y-m-d H:i:s', $date_int);
    }

    /**
     * 设置脱敏信息解密ID
     * @param string $module
     * @param int $number
     * @param array $ids
     * @return bool
     */
    public static function setDesensitizationDecryptId(string $module, int $number, array $ids)
    {
        if (empty($ids)) {
            return true;
        }
        $key    = 'data_center.finance.' . $module . '.number_' . $number;
        $addIds = [];
        $time   = time();
        foreach ($ids as $id) {
            $addIds[] = $time;
            $addIds[] = $id;
        }
        Yii::$app->redis->zadd($key, ...$addIds);
        Yii::$app->redis->expire($key, env('SENSITIVE_INFO_DECRYPT_DURATION', 1800));
        return true;
    }

    /**
     * 获取脱敏信息解密ID
     * @param string $module
     * @param int $number
     * @return array
     */
    public static function getDesensitizationDecryptId(string $module, int $number)
    {
        $key             = 'data_center.finance.' . $module . '.number_' . $number;
        $decryptDuration = env('SENSITIVE_INFO_DECRYPT_DURATION', 1800);
        Yii::$app->redis->zremrangebyscore($key, 0, time() - $decryptDuration);
        return Yii::$app->redis->zrange($key, 0, -1);
    }

    /**
     * @param $str
     * @return array|string|string[]|null
     */
    public static function filterSpecialCharacters($str)
    {
        $str     = str_replace(' ', '', $str);
        $pattern = "/[\n\t\a\f\r\v\b]/";

        $filteredStr = preg_replace($pattern, '', $str);
        return self::filterHiddenCharacters($filteredStr);
    }

    /**
     * @param $str
     * @return array|string|string[]|null
     */
    public static function filterHiddenCharacters($str)
    {
        // 使用正则表达式匹配所有的隐藏字符，然后移除它们
        $filter = [" ", "‬", "　", "ㅤ", "⠀", " "];
        $str    = str_replace($filter, '', $str);
        return trim(preg_replace('/[[:cntrl:]]/', '', $str));
    }

    /**
     * 推送消息
     * @param $exchangeName
     * @param $queueName
     * @param $callback
     * @param $routingKey
     * @param $config
     * @return bool
     * @throws ErrException
     * @throws \AMQPChannelException
     * @throws \AMQPConnectionException
     * @throws \AMQPExchangeException
     * @throws \AMQPQueueException
     */
    public static function pushRabbitMQMsg($exchangeName, $queueName, $callback, $routingKey = null, $exchangeType = null, $config = []): bool
    {
        if (empty($config)) {
            $config = \Yii::$app->params["amqp"];
        }
        //建立连接
        $connection = new \AMQPConnection();
        $connection->setHost($config['host']);
        $connection->setPort($config['port']);
        $connection->setLogin($config['login']);
        $connection->setPassword($config['password']);
        $connection->setVhost($config['vhost']);
        if (!$connection->connect()) {
            throw new ErrException(Code::DATA_ERROR, '连接MQ失败');
        }
        //建立通道
        $channel = new \AMQPChannel($connection);
        //定义交换器
        $exchange = new \AMQPExchange($channel);
        //交换器名称
        $exchange->setName($exchangeName);
        if (!$exchangeType) {
            // 交换器类型（直连）
            $exchangeType = AMQP_EX_TYPE_DIRECT;
        }
        $exchange->setType($exchangeType);
        //交换器标签
        $exchange->setFlags(AMQP_DURABLE);
        //创建交换器
        $exchange->declareExchange();
        if ($queueName) {
            // 定义队列
            $queue = new \AMQPQueue($channel);
            // 队列名称
            $queue->setName($queueName);
            // 队列标签
            $queue->setFlags(AMQP_DURABLE);
            // 创建队列
            $queue->declareQueue();
            // 绑定交换器
            $queue->bind($exchangeName, $routingKey);
        }
        try {
            //$message = is_string($message) ? $message : json_encode($message);
            //$exchange->publish($message, $routingKey);
            if (is_callable($callback)) {
                $callback($exchange);
            }
        } catch (\Exception $e) {
            \Yii::warning($e->getMessage());
        }
        // 关闭通道和连接
        $channel->close();
        $connection->disconnect();
        return true;
    }

    /**
     * 消费MQ
     * @param $ex
     * @param $que
     * @param $callback
     * @param $qosCount * 预取消息数量
     * @param $rk
     * @param $config
     * @return void
     */
    public static function consoleConsumptionMQ($ex, $que, $callback, $qosCount = 1, $rk = null, $exchangeType = null, $config = [])
    {
        if (empty($config)) {
            $config = \Yii::$app->params["amqp"];
        }
        try {
            // 建立连接
            $connection = new \AMQPConnection();
            $connection->setHost($config['host']);
            $connection->setPort($config['port']);
            $connection->setLogin($config['login']);
            $connection->setPassword($config['password']);
            $connection->setVhost($config['vhost']);
            if (!$connection->connect()) {
                throw new \AMQPConnectionException('连接MQ失败!');
            }
            // 建立通道
            $channel = new \AMQPChannel($connection);
            $channel->qos(0, $qosCount);
            // 定义交换器
            $exchange = new \AMQPExchange($channel);
            // 交换器名称
            $exchange->setName($ex);
            if (!$exchangeType) {
                // 交换器类型（直连）
                $exchangeType = AMQP_EX_TYPE_DIRECT;
            }
            $exchange->setType($exchangeType);
            // 交换器标签
            $exchange->setFlags(AMQP_DURABLE);
            // 创建交换器
            $exchange->declareExchange();

            // 定义队列
            $queue = new \AMQPQueue($channel);
            // 队列名称
            $queue->setName($que);
            // 队列标签
            $queue->setFlags(AMQP_DURABLE);
            // 创建队列
            $queue->declareQueue();
            $queue->bind($ex, $rk);

            // 消费消息
            $queue->consume(function (\AMQPEnvelope $envelope, \AMQPQueue $queue) use ($callback, $ex, $que) {
                \Yii::warning($ex . "【消息】：" . $envelope->getBody());
                $data = json_decode($envelope->getBody(), true);
                try {
                    if (is_callable($callback)) {
                        $callback($data);
                    }
                } catch (\Exception $e) {
                    self::outputLog($que . "【消息消费失败】：" . $e->getMessage());
                }
                $queue->ack($envelope->getDeliveryTag());
            });
        } catch (\Exception $e) {
            // 关闭通道和连接
            if (isset($channel) && $channel->isConnected()) {
                $channel->close();
            }
            if (isset($connection) && $connection->isConnected()) {
                $connection->disconnect();
            }
            echo $e->getMessage() . PHP_EOL;
        }
    }

    /**
     * 用SHA1算法生成企业微信安全签名
     * @param $token
     * @param $timestamp
     * @param $nonce
     * @param $msgEncrypt
     * @return string
     */
    public static function getWorkWechatDevMsgSignature($timestamp, $nonce, $msgEncrypt): string
    {
        $array = array(\Yii::$app->params['workWechat']['callbackEncodingAESKey'], $timestamp, $nonce);
        if ($msgEncrypt) {
            $array[] = $msgEncrypt;
        }
        sort($array, SORT_STRING);
        return sha1(implode($array));
    }

    /**
     * 加密企业微信消息
     * @param $data
     * @param $key
     * @return string
     */
    public function getWorkWechatMsgEncrypt($data)
    {
        $data = openssl_encrypt($data, 'AES-256-CBC', \Yii::$app->params['workWechat']['callbackEncodingAESKey'], OPENSSL_RAW_DATA);
        return base64_encode($data);
    }

    /**
     * 解密企业微信消息
     * @param $data
     * @return array
     */
    public static function getWorkWechatMsgDecrypt($data): array
    {
        if ($data) {
            $encrypted = base64_decode($data);
            $AESKey    = base64_decode(\Yii::$app->params['workWechat']['callbackEncodingAESKey'] . "=");
            $randMsg   = openssl_decrypt($encrypted, 'AES-256-CBC', $AESKey, OPENSSL_RAW_DATA);
            // 去掉前16随机字节
            $content = substr($randMsg, 16);
            $msgLen  = 0;
            $msg     = '';
            if (!empty($content)) {
                // 取出4字节的msg_len
                $msgLen = unpack('N', substr($content, 0, 4))[1];
                // 截取msg_len长度的msg
                $msg = substr($content, 4, $msgLen);
            }
            // 剩余字节为receiveId
            $receiveId = substr($content, $msgLen + 4);
        }
        if (!empty($msg)) {
            if (self::isXml($msg)) {
                $xml = simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA);
                $msg = json_decode(json_encode($xml), true);
            }
        }
        return [
            'msgLen'    => $msgLen ?? 0,
            'msg'       => $msg ?? '',
            'receiveId' => $receiveId ?? ''
        ];
    }

    /**
     * 对解密后的明文进行补位删除
     *
     * @param string $text 解密后的明文
     * @return string 删除填充补位后的明文
     */
    public static function PKCS7Decode($text)
    {
        $pad = ord(substr($text, -1));
        if ($pad < 1 || $pad > 32) {
            $pad = 0;
        }
        return substr($text, 0, (strlen($text) - $pad));
    }

    /**
     * @param $string
     * @return bool
     */
    public static function isXml($string)
    {
        // 禁止错误输出
        libxml_use_internal_errors(true);
        // 尝试解析 XML 字符串
        simplexml_load_string($string, 'SimpleXMLElement', LIBXML_NOCDATA);
        // 获取解析过程中的错误
        $errors = libxml_get_errors();
        // 恢复错误输出设置
        libxml_use_internal_errors(false);
        // 如果存在错误，则返回 false；否则返回 true
        return empty($errors);
    }


    /**
     * 把数据集转换成Tree
     * @param array $list 要转换的数据集
     * @param int $root parent_id
     * @param string $pk 主键字段名
     * @param string $pid 父级id 字段名
     * @param string $child
     * @return array
     */
    public static function toTreeByList($list, $root = 0, $pk = 'id', $pid = 'parent_id', $child = 'children')
    {
        // 创建Tree
        $tree = array();
        if (is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] = &$list[$key];
            }
            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId = $data[$pid];
                if ($root == $parentId) {
                    $tree[] = &$list[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent           = &$refer[$parentId];
                        $parent[$child][] = &$list[$key];
                    }
                }
            }
        }
        return $tree;
    }

    /**
     * 将的树还原成列表
     * @param array $tree 原来的树
     * @param string $child 孩子节点的键
     * @param string $order 排序显示的键，一般是主键 升序排列
     * @param array $list 过渡用的中间数组，
     * @return array        返回排过序的列表数组
     */
    public static function toListByTree($tree, $child = 'children', $order = 'id', &$list = array())
    {
        if (is_array($tree)) {
            $refer = array();
            foreach ($tree as $key => $value) {
                $refer = $value;
                if (isset($refer[$child])) {
                    unset($refer[$child]);
                    self::toListByTree($value[$child], $child, $order, $list);
                }
                $list[] = $refer;
            }
            $list = self::sortByToList($list, $order, 'asc');
        }
        return $list;
    }

    /**
     * 对查询结果集进行排序
     * @param array $list 查询结果
     * @param string $field 排序的字段名
     * @param array $sortby 排序类型
     *  asc正向排序 desc逆向排序 nat自然排序
     * @return array|false
     */
    public static function sortByToList($list, $field, $sortby = 'asc')
    {
        if (is_array($list)) {
            $refer = $resultSet = array();
            foreach ($list as $i => $data)
                $refer[$i] = &$data[$field];
            switch ($sortby) {
                case 'asc': // 正向排序
                    asort($refer);
                break;
                case 'desc': // 逆向排序
                    arsort($refer);
                break;
                case 'nat': // 自然排序
                    natcasesort($refer);
                break;
            }
            foreach ($refer as $key => $val)
                $resultSet[] = &$list[$key];
            return $resultSet;
        }
        return false;
    }

    /**
     * Generate a more truly "random" alpha-numeric string.
     * @return string
     * @throws \Exception
     */
    public static function randomStr($length = 16): string
    {
        $string = '';
        while (($len = strlen($string)) < $length) {
            $size   = $length - $len;
            $bytes  = random_bytes($size);
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
        }
        return $string;
    }

    /**
     * 生成英文数字随机字符串
     * @param $length
     * @return string
     */
    public static function getRandomStr($length = 16): string
    {
        $str     = '';
        $str_pol = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyl';
        $max     = strlen($str_pol) - 1;
        for ($i = 0; $i < $length; $i++) {
            $str .= $str_pol[mt_rand(0, $max)];
        }
        return $str;
    }

    /**
     * 返回带有毫秒的日期时间
     * @return string
     */
    public static function getMicroDatetime()
    {
        $result    = date('Y-m-d H:i:s.');
        $microTime = strval(round(microtime(true), 4));
        $microTime = explode('.', $microTime);
        if (is_array($microTime) && count($microTime) == 2) {
            $microTime = $microTime[1];
        } else {
            $microTime = '0';
        }
        $microTime = str_pad($microTime, 4, '0');
        return $result . $microTime;
    }

    /**
     * 获取必填字符串参数
     * @param $params
     * @param array $attributeLabels
     * @param string $field
     * @return float|int|string
     * @throws ErrException
     */
    public static function getRequireString($params, array $attributeLabels, string $field)
    {
        $val = self::getString($params, $field);
        if (!$val) {
            throw new ErrException(Code::PARAMS_ERROR, sprintf('%s不能为空', $attributeLabels[$field] ?? $field));
        }
        return $val;
    }

    /**
     * 获取枚举int参数
     * @param $params
     * @param array $attributeLabels
     * @param string $field
     * @param array $enum
     * @return int|null
     * @throws ErrException
     */
    public static function getEnumInt($params, array $attributeLabels, string $field, array $enum)
    {
        $val = self::getInt($params, $field);
        if (!in_array($val, $enum)) {
            throw new ErrException(Code::PARAMS_ERROR, sprintf('%s参数值不支持', $attributeLabels[$field] ?? $field));
        }
        return $val;
    }

    /**
     * @param $str
     * @return true
     */
    public static function outputLog($str): bool
    {
        if (\Yii::$app->id != "console") {
            \Yii::warning($str);
        } else {
            echo sprintf('[%s] %s', self::getMicroDatetime(), $str) . PHP_EOL;
        }
        return true;
    }

    /**
     * 从response_data中提取并解析JSON内容
     * @param string $responseData 原始response_data内容
     * @return mixed|null 解析后的JSON数据，解析失败返回null
     */
    public static function extractJsonFromMarkdown(string $responseData)
    {
        // 检查是否为空
        if (empty($responseData)) {
            self::outputLog('response_data为空');
            return null;
        }

        // 尝试匹配Markdown代码块格式 ```json\n...\n```
        if (preg_match('/```json\\n(.*?)\\n```/s', $responseData, $matches)) {
            $jsonContent = $matches[1];
            $result      = json_decode($jsonContent, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $result;
            } else {
                self::outputLog("从Markdown提取的内容解析JSON失败: " . json_last_error_msg());
            }
        }

        // 尝试直接解析，可能不是Markdown格式
        $result = json_decode($responseData, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $result;
        }
        self::outputLog("无法解析response_data内容，既不是有效的Markdown代码块也不是有效的JSON");
        return null;
    }
}