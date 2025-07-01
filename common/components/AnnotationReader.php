<?php

namespace common\components;

use ReflectionClass;
use Yii;

/**
 * 注解读取器
 */
class AnnotationReader
{
    /**
     * 获取控制器方法上的注解
     * @return false|string|null
     * @throws \ReflectionException
     */
    public static function getAnnotation()
    {
        $controller = Yii::$app->controller;
        $reflection = new ReflectionClass($controller);
        $action = Yii::$app->requestedAction;
        $method = $reflection->getMethod($action->actionMethod);
        if (!$controller || !$action instanceof \yii\base\InlineAction){
            return null;
        }
        return $method->getDocComment();
    }

    /**
     * 读取方法上的actionLog注解
     * @example actionLog(model="坐席管理", action="列表", description="绑定微信坐席" )
     * @return array|null
     */
    public static function getLogAnnotation(): ?array
    {
        try {
            $docComment = self::getAnnotation();
            if (!$docComment){
                return null;
            }
            // 解析注解
            if (preg_match('/@actionLog\((.*?)\)/s', $docComment, $matches)) {
                $paramsString = $matches[1];
                $params = [];
                // 解析键值对参数
                preg_match_all('/(\w+)=["\']?(.*?)["\']?(?=[,\s\)])/', $paramsString, $paramMatches);
                foreach ($paramMatches[1] as $index => $key) {
                    $params[$key] = trim($paramMatches[2][$index], '"\'');
                }
                return $params;
            }
            return null;
        } catch (\ReflectionException $e) {
            return null;
        }
    }
}

