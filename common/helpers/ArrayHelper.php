<?php

namespace common\helpers;

/**
 * Class ArrayHelper
 * @package common\helpers
 */
class ArrayHelper extends \yii\helpers\ArrayHelper
{

    /**
     * @param array $data
     * @return \stdClass
     */
    public static function toObject($data)
    {
        $std = new \StdClass();
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $std->$key = $value;
            }
        }
        return $std;
    }

    /**
     * @param array $data
     * @return array
     */
    public static function toKeyValues($data)
    {
        $result = [];
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $result[] = [
                    'key'   => $key,
                    'value' => $value
                ];
            }
        }
        return $result;
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
     * 数组分组
     * @author 龚德铭
     * date 21.1.20
     * @param array $data
     * @param string $key 根据某一键分组
     */
    public static function dataGroup($data, $key)
    {
        $return = [];
        foreach ($data as $val) {
            if (!isset($val[$key])) {
                break;
            }

            $return[$val[$key]][] = $val;
        }

        return $return;
    }

    /**
     * 判断是否为多维数组
     * @param $data
     * @return bool
     * 龚德铭
     * 2021/12/2 10:33
     */
    public static function isManyArray($data){
        if(count($data) == count($data, 1)){
            return false;
        } else {
            return true;
        }
    }

    /**
     * 键别名
     * @param $data
     * @param $key
     * @param bool $isDel
     * @return mixed
     * 龚德铭
     * 2022/2/18 14:32
     */
    public static function keyAlias($data, $key, $isDel = true){
        foreach($key as $k_k => $k_v){
            if(isset($data[$k_k])){
                $data[$k_v] = $data[$k_k];
                if($isDel){
                    unset($data[$k_k]);
                }
            }
        }

        return $data;
    }

    /**
     * 格式化枚举数据
     * @param $data
     * @param $labels
     * @return array
     * @author 龚德铭
     * @date 2023/8/30 18:08
     */
    public static function formatLabels($data, $labels)
    {
        $data   = is_array($data) ? $data : [$data];
        $return = [];
        foreach ($data as $dv) {
            $temp = self::getValue($labels, $dv);
            if (!empty($temp)) {
                $return[] = $temp;
            }
        }

        return $return;
    }

}