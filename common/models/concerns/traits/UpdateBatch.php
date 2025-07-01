<?php

namespace common\models\concerns\traits;

/**
 * @method static string tableName()
 */
trait UpdateBatch
{
    /**
     * 批量更新
     *
     * 取名V2是为了跟之前的 updateBatch 方法分开来
     * 之前的方法依赖于模型场景
     * 这个方法不需要依赖模型场景, 直接拼接SQL语句, 可直接使用
     *
     * @param array $inputs
     * @param string $whereField
     * @param string $whenField
     * @return mixed
     *
     * [['id' => 1, 'name' => 'a'], ['id' => 2, 'name' => 'b']]
     *
     * update users set name =
     *    case
     *    when id = 1 then 'a'
     *    when id = 2 then 'b'
     * where id in (1,2);
     */
    public static function updateBatch($inputs = [], $whereField = 'id', $whenField = 'id')
    {
        if (empty($inputs)) {
            throw new \InvalidArgumentException('The update data is empty.');
        }

        $tableName = static::tableName(); // 表名
        $firstRow  = current($inputs);

        $updateColumn = array_keys($firstRow);
        // 默认以id为条件更新，如果没有ID则以第一个字段为条件
        // $referenceColumn = isset($firstRow['id']) ? 'id' : current($updateColumn);
        $referenceColumn = $whenField;
        unset($updateColumn[array_search($referenceColumn, $updateColumn)]);
        // 拼接sql语句
        $updateSql = "UPDATE " . $tableName . " SET ";
        $sets      = [];
        $bindings  = [];
        foreach ($updateColumn as $uColumn) {
            $setSql = "`" . $uColumn . "` = CASE ";
            foreach ($inputs as $data) {
                $setSql     .= "WHEN `" . $referenceColumn . "` = ? THEN ? ";
                $bindings[] = $data[$referenceColumn];
                $bindings[] = $data[$uColumn];
            }
            $setSql .= "ELSE `" . $uColumn . "` END ";
            $sets[] = $setSql;
        }
        $updateSql .= implode(', ', $sets);
        $whereIn   = array_column($inputs, $referenceColumn);
        $bindings  = array_merge($bindings, $whereIn);
        $whereIn   = rtrim(str_repeat('?,', count($whereIn)), ',');
        $updateSql = rtrim($updateSql, ", ") . " WHERE `" . $whereField . "` IN (" . $whereIn . ")";

        $command = \Yii::$app->db->createCommand($updateSql);

        foreach ($bindings as $index => $value) {
            $command->bindValue($index + 1, $value);
        }

        return $command->execute();
    }


}