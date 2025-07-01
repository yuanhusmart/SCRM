<?php

namespace common\helpers;

class Tree
{

    public static function make()
    {
        return new static();
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
    public function toTree($list, $root = 0, $pk = 'id', $pid = 'parent_id', $child = 'children')
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
    public function toList($tree, $child = 'children', $order = 'id', &$list = array())
    {
        if (is_array($tree)) {
            $refer = array();
            foreach ($tree as $key => $value) {
                $refer = $value;
                if (isset($refer[$child])) {
                    unset($refer[$child]);
                    $this->toList($value[$child], $child, $order, $list);
                }
                $list[] = $refer;
            }
            $list = $this->sortBy($list, $order, 'asc');
        }
        return $list;
    }

    /**
     * 对查询结果集进行排序
     * @param array $list 查询结果
     * @param string $field 排序的字段名
     * @param array $sortby 排序类型
     * asc正向排序 desc逆向排序 nat自然排序
     * @return array
     */
    function sortBy($list, $field, $sortby = 'asc')
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
}
