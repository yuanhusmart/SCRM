<?php

namespace common\helpers;

use common\services\Service;
use yii\db\Query;

class Utils
{
    /**
     * 简便的分页实现
     * @param Query $query
     * @param array $params
     * @param string|null $model
     * @param string $items
     * @param callable|null $callback
     * @return array
     */
    public static function pagination(Query $query, array $params,string $model = null, string $items = 'items',callable $callback = null):? array
    {
        list($page, $per_page) = Service::getPageInfo($params);
        $model && $model = new $model();
        $total = $query->count();
        $offset = ($page - 1) * $per_page;
        $resp = [];
        if ($total > 0) {
            $resp = $query->offset($offset)->limit($per_page)->asArray()->all();
            foreach ($resp as &$item){
                //判断model里面是否存在transform方法
                if ($model && method_exists($model, 'transform')){
                    /**
                     * @uses \common\db\ActiveRecord::transform()
                     */
                    $item = $model::transform($item);
                }
                $callback && $item = call_user_func($callback, $item);
            }
            unset($item);
        }
        return [
            $items => $resp,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => intval($total)
            ]
        ];
    }

    public static function aesEncode($string, $key) {
        $strArr   = str_split(base64_encode($string));
        $strCount = count($strArr);
        foreach (str_split($key) as $key => $value) {
            $key < $strCount && $strArr[$key] .= $value;
        }
        return str_replace(array('=', '+', '/'), array('i1ll1', '1ll1', 'll1l'), join('', $strArr));
    }

    public static function aesDecode($string, $key) {
        $strArr   = str_split(str_replace(array('i1ll1', '1ll1', 'll1l'), array('=', '+', '/'), $string), 2);
        $strCount = count($strArr);
        foreach (str_split($key) as $key => $value) {
            $key <= $strCount && isset($strArr[$key]) && $strArr[$key][1] === $value && $strArr[$key] = $strArr[$key][0];
        }
        return base64_decode(join('', $strArr));
    }
}
