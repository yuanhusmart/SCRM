<?php

namespace common\db;

use Yii;
use yii\db\Connection;

class EventActiveRecord extends ActiveRecord
{
    const EVENT_AFTER_COMMIT_UPDATE       = 'afterCommitUpdate';
    const EVENT_AFTER_COMMIT_INSERT       = 'afterCommitInsert';
    const EVENT_AFTER_COMMIT_DELETE       = 'afterCommitDelete';
    const EVENT_AFTER_COMMIT_BATCH_INSERT = 'afterCommitBatchInsert';
    const EVENT_AFTER_COMMIT_DELETE_ALL   = 'afterCommitDeleteAll';
    const EVENT_FILE_METHOD               = 'getEventFile';
    const EVENT_METHOD_INSERT             = 'insert';
    const EVENT_METHOD_UPDATE             = 'update';
    const EVENT_METHOD_DELETE             = 'delete';
    const EVENT_METHOD_BATCH_INSERT       = 'batchInsert';


    /**
     * 分批插入
     * @param $data
     * @param int $limit
     * 龚德铭
     * 2021/8/27 10:10
     */
    public static function batchInsertGroup($data, $limit = 100)
    {
        $tempData = $data;
        $return   = 0;
        while (true) {
            $group = array_splice($data, 0, $limit);
            if (empty($group)) {
                break;
            }
            $return += Yii::$app->db->createCommand()
                                    ->batchInsert(static::tableName(), array_keys(reset($group)), $group)
                                    ->execute();
        }

        $triggerParams = [
            'attributes' => $tempData
        ];
        self::callTrigger(self::EVENT_AFTER_COMMIT_BATCH_INSERT, $triggerParams);
        return $return;
    }

    /**
     * 新增数据
     * @param bool $runValidation
     * @param null $attributes
     * @param array $triggerParams
     * @return bool
     * @throws \Throwable
     * @author 龚德铭
     * @date 2023/5/29 17:28
     */
    public function insert($runValidation = true, $attributes = null, $triggerParams = [])
    {
        $return        = parent::insert($runValidation, $attributes);
        $triggerParams = !empty($triggerParams) ? $triggerParams : [
            'attributes' => $this->attributes,
        ];

        self::callTrigger(self::EVENT_AFTER_COMMIT_INSERT, $triggerParams);
        return $return;
    }

    /**
     * 删除数据
     * @param array $triggerParams
     * @return false|int
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     * @author 龚德铭
     * @date 2023/5/29 17:44
     */
    public function delete($triggerParams = [])
    {
        $triggerParams = !empty($triggerParams) ? $triggerParams : [
            'attributes' => $this->attributes,
        ];
        self::callTrigger(self::EVENT_AFTER_COMMIT_DELETE, $triggerParams);
        return parent::delete();
    }

    /**
     * 修改数据
     * @param array $attributes
     * @param string $condition
     * @param array $params
     * @param array $triggerParams
     * @return int
     * @author 龚德铭
     * @date 2023/5/29 17:28
     */
    public static function updateAll($attributes, $condition = '', $params = [], $triggerParams = [])
    {
        // 获取历史数据
        $data = parent::updateAll($attributes, $condition, $params);
        if (is_array($condition)) {
            $attributes = array_merge($condition, $attributes);
        }
        $triggerParams = !empty($triggerParams) ? $triggerParams : [
            'attributes' => $attributes,
        ];
        self::callTrigger(self::EVENT_AFTER_COMMIT_UPDATE, $triggerParams);

        return $data;
    }

    /**
     * 回调事件
     * @param $attributes
     * @param $oldAttributes
     * @return bool
     * @author 龚德铭
     * @date 2023/5/29 16:48
     */
    public static function callTrigger($eventName, $triggerParams)
    {
        $className = self::getCallClass();
        $className = "\common\components\\events\\{$className}";

        if (!class_exists($className)) {
            $method = self::EVENT_FILE_METHOD;
            if (!method_exists(static::class, $method)) {
                return true;
            }
            $className = static::$method();
            if (!class_exists($className)) {
                return true;
            }
        }
        //如果开启事务绑定事务提交事件
        if (\Yii::$app->db->transaction) {
            \Yii::$app->db->on(Connection::EVENT_COMMIT_TRANSACTION, function () use ($eventName, $triggerParams, $className) {
                \Yii::$app->db->off(Connection::EVENT_COMMIT_TRANSACTION);
                self::triggerAfterCommitUpdateEvent($eventName, new $className($triggerParams));
            });
        } else {
            self::triggerAfterCommitUpdateEvent($eventName, new $className($triggerParams));
        }

        return true;
    }

    /**
     * 触发事件
     * @param $event
     */
    protected static function triggerAfterCommitUpdateEvent($eventName,$event)
    {
        $model = new self();
        $model->trigger($eventName, $event);
    }


    public static function getCallClass()
    {
        $className = explode('\\', get_called_class());
        return end($className) . 'Event';
    }

    /**
     * 批量插入数据 忽略已存在唯一键
     * @param $tableName
     * @param $fieldKeys
     * @param $attributes
     * @return int
     * @throws \yii\db\Exception
     */
    public static function batchInsertIgnore($tableName, $fieldKeys, $attributes){
        $sql = 'INSERT IGNORE INTO `'.$tableName.'` (';
        $sql .= array_reduce($fieldKeys, function ($last, $v) {
            return ($last ? $last . ',' : '') . ' `' . $v . '`';
        });
        $sql .= ') VALUES ';
        $insertData = [];
        foreach ($attributes as $key => $attribute) {
            $sql .= array_reduce($fieldKeys, function ($last, $v) use($key) {
                return ($last ? $last . ',' : ' (') . ":{$v}_{$key}";
            });
            $sql .= '),';
            foreach ($fieldKeys as $fieldKey) {
                $insertData[":{$fieldKey}_{$key}"] = $attribute[$fieldKey];
            }
        }
        $sql = rtrim($sql, ',');
        $sql .= ';';
        return \Yii::$app->db->createCommand($sql, $insertData)->execute();
    }
}