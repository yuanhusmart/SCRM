<?php

namespace common\errors;

/**
 * 通用错误异常
 * Class ErrException
 * @package common\errors
 */
class ErrException extends \Exception
{

    public $status = 500;

    /**
     * 构造方法
     * ErrException constructor.
     * @param int $code
     * @param string $message
     * @param int|null $status
     * @param \Throwable|null $previous
     */
    public function __construct($code = 0, $message = '', $status = null, \Throwable $previous = null)
    {
        $infos = Code::statusMessages();
        if ($status) {
            $this->status = $status;
        } else {
            if (isset($infos[$code]['status'])) {
                $this->status = $infos[$code]['status'];
            }
        }
        if (!$message && isset($infos[$code]['message'])) {
            $message = $infos[$code]['message'];
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * 获取异常的跟踪信息，字符串格式
     * @param \Exception $e
     * @return string
     */
    public static function getTraceString($e)
    {
        $trace = self::getTraceInfo($e);
        return implode(PHP_EOL, $trace);
    }

    /**
     * 获取异常的跟踪信息
     * @param \Exception $e
     * @return string[]
     */
    public static function getTraceInfo($e)
    {
        $trace = explode(PHP_EOL, $e->getTraceAsString());
        return array_merge(array('## ' . $e->getFile() . '(' . $e->getLine() . ')'), $trace);
    }

    /**
     * 获取异常指定的 HTTP 状态码
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $e
     * @return string
     */
    public static function getFriendlyMessage($e)
    {
        $message = $e->getMessage();
        preg_match('/^(SQLSTATE\[.*?\]).*$/m', $message, $matches);
        if (is_array($matches) && isset($matches[1])) {
            $message = '数据错误 ' . $matches[1];
        }
        return $message;
    }
}