<?php

namespace console\controllers;


use common\services\Service;
use yii\console\Controller;
use yii\helpers\Inflector;

/**
 * 所有控制台控制器的基类
 * Class BaseConsoleController
 * @package console\controllers
 */
class BaseConsoleController extends Controller
{

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);
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
     * @return string
     */
    public static function getMicroDatetime()
    {
        return Service::getMicroDatetime();
    }

    /**
     * @param $class
     * @param $actionName
     * @return string
     */
    protected static function getClassActionRoute($class, $actionName): string
    {
        // 创建反射类对象
        $reflectionClass = new \ReflectionClass($class);
        // 获取类中的所有公共方法
        $methods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);
        // 过滤出动作方法
        $route = '';
        foreach ($methods as $method) {
            if (strpos($method->name, 'action') === 0 && $actionName == $method->name && $method->isPublic() && !$method->isStatic() && $class === $method->class) {
                $route = Inflector::camel2id(
                        substr(basename($method->getFileName()), 0, -14), '-', true
                    ) .
                         '/' .
                         mb_strtolower(trim(
                             preg_replace('/\p{Lu}/u', '-\0',
                                 strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', substr($method->name, 6)))
                             ), '-'
                         ), 'UTF-8');
            }
        }
        return $route;
    }

}