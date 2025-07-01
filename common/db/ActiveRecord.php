<?php

namespace common\db;

use common\errors\Code;
use common\errors\ErrException;
use common\models\concerns\traits\ToArray;
use common\models\concerns\traits\With;
use Yii;
use yii\db\BaseActiveRecord;
use yii\db\Exception;

/**
 * 所有数据模型的基类
 * Class ActiveRecord
 * @package common\db
 */
class ActiveRecord extends \yii\db\ActiveRecord
{
    use With, ToArray;

    /**
     * @var bool 是否使用雪花算法生成 ID
     */
    public $useSnowFlake = false;

    /**
     * @return ActiveQuery
     */
    public static function find()
    {
        return Yii::createObject(ActiveQuery::class, [get_called_class()]);
    }

    /**
     * 格式化模型查询结果的数据类型
     * @param BaseActiveRecord $record
     * @param array $row
     * @throws \Exception
     */
    public static function populateRecord($record, $row)
    {
        $columns = static::getTableSchema()->columns;
        foreach ($row as $name => $value) {
            if (isset($columns[$name])) {
                if ($name == 'company_id') {
                    $columns[$name]->phpType = 'integer';
                } elseif ($name == 'id' || $columns[$name]->type == 'bigint') {
                    $columns[$name]->phpType = 'string';
                }
            }
        }
        parent::populateRecord($record, $row);
    }

    /**
     * 保存之前执行
     * @param bool $insert
     * @return bool
     * @throws \Exception
     */
    public function beforeSave($insert)
    {
        if ($this->isNewRecord && $this->useSnowFlake && $this->hasAttribute('id') && !$this->id) {
            $this->id = Yii::$app->snowFlake->generateId();
        }

        return parent::beforeSave($insert);
    }


    /**
     * 获取第一条错误信息
     * @return string
     */
    public function getError()
    {
        $errors = $this->getErrors();
        foreach ($errors as $items) {
            $message = current($items);
            if (mb_substr($message, -1) == '。') {
                $message = mb_substr($message, 0, -1);
            }
            return trim($message);
        }
        return '';
    }

    /**
     * 分批插入
     * @param array $data
     * @param int $limit
     * @throws Exception
     */
    public static function batchInsert($data, $limit = 100)
    {
        while (true) {
            $items = array_splice($data, 0, $limit);
            if (empty($items)) {
                break;
            }

            Yii::$app->db->createCommand()
                ->batchInsert(static::tableName(), array_keys(reset($items)), $items)
                ->execute();
        }
    }

    /**
     * @param array $changed_attributes
     * @return array|string
     */
    public function getLogOldAttributes($changed_attributes)
    {
        $is_null = true;
        foreach ($changed_attributes as $value) {
            if ($value !== null) {
                $is_null = false;
                break;
            }
        }
        if ($is_null) {
            return '';
        } else {
            return $changed_attributes;
        }
    }

    /**
     * 设置模型的属性
     * @param array $values
     * @param bool $safeOnly
     * @throws \Exception
     */
    public function setAttributes($values, $safeOnly = true)
    {
        $columns = static::getTableSchema()->columns;
        foreach ($values as $key => $value) {
            if (!isset($columns[$key])) {
                continue;
            }
            if ($value === '') {
                if (in_array($columns[$key]->type, ['tinyint', 'smallint', 'integer', 'bigint', 'float', 'decimal', 'timestamp', 'money'])) {
                    unset($values[$key]);
                }
            } elseif ($value === null) {
                if (in_array($columns[$key]->type, ['tinyint', 'smallint', 'integer', 'bigint', 'float', 'decimal', 'timestamp', 'money'])) {
                    unset($values[$key]);
                } elseif (in_array($columns[$key]->phpType, ['string'])) {
                    $values[$key] = '';
                }
            } elseif (is_numeric($value)) {
                if (in_array($columns[$key]->phpType, ['string'])) {
                    $values[$key] = strval($values[$key]);
                }
            }
        }
        parent::setAttributes($values, $safeOnly);
    }

    /**
     * 日期时间字段和当前时间做比较
     * @param $attribute
     */
    public function validateCurrentTime($attribute)
    {
        if ($this->isNewRecord && $this->$attribute && !$this->hasErrors($attribute) && $this->$attribute < time()) {
            $this->addError($attribute, $this->getAttributeLabel($attribute) . '不能小于当前时间');
        }
    }

    /**
     * 修改一条数据
     * @param $condition
     * @param $update
     * @return bool
     */
    public static function updateOne($condition, $update)
    {
        $model = static::find()->where($condition)->one();
        if (!$model) {
            Yii::warning("数据修改：【" . static::className() . "】异常，参数：" . json_encode(['condition' => $condition, 'update' => $update], JSON_UNESCAPED_UNICODE));
            return false;
        }
        $model->attributes = $update;
        if (!$model->save()) {
            Yii::warning("数据修改：【" . static::className() . "】异常，参数：" . json_encode(['condition' => $condition, 'update' => $update], JSON_UNESCAPED_UNICODE));
            return false;
        }
        return true;
    }

    /**
     * 创建没有的数据并返回模型
     * @param $where
     * @param array $attribute
     * @return array|ActiveRecord|\yii\db\ActiveRecord|static
     * @throws ErrException
     * @author 龚德铭
     * @date 2021-05-29 14:53
     */
    public static function createNotExistsData($where, $attribute = [])
    {
        $model = static::find()->where($where)->one();
        if ($model) {
            return $model;
        }
        $model = new static();
        $model->loadDefaultValues();
        $model->attributes = array_merge($where, $attribute);
        if (!$model->save()) {
            throw new ErrException(Code::PARAMS_ERROR, $model->getError());
        }
        return $model;
    }

    /**
     * 分批插入
     * @param $data
     * @param int $limit
     * @throws Exception
     * 龚德铭
     * 2021/8/27 10:10
     */
    public static function batchInsertGroup($data, $limit = 100)
    {
        while (true) {
            $grop = array_splice($data, 0, $limit);
            if (empty($grop)) {
                break;
            }
            Yii::$app->db->createCommand()
                         ->batchInsert(static::tableName(), array_keys(reset($grop)), $grop)
                         ->execute();
        }
    }

    /**
     * @param $attributes
     * @param $condition
     * @param $params
     * @return int
     * @throws \yii\base\InvalidConfigException
     */
    public static function updateAll($attributes, $condition = '', $params = [])
    {
        if (self::getTableSchema()->getColumn('updated_at')) {
            $attributes['updated_at'] = time();
        }
        return parent::updateAll($attributes, $condition, $params);
    }

    /**
     * 查找或创建模型，不存在则创建，存在则更新（无事务版）
     *
     * @param array $conditions 查找条件（数组或Query条件）
     * @param array $attributes 更新或创建的属性
     * @param bool $validate 是否在保存时验证数据
     * @return static 保存后的模型实例
     * @throws \yii\db\Exception 保存失败时抛出异常
     */
    public static function updateOrCreate($conditions, $attributes = [], $validate = true)
    {
        // 尝试查找符合条件的记录
        $model = static::find()->where($conditions)->one();
        if ($model === null) {
            // 不存在则创建新模型，合并条件和属性
            $model = new static();
            $model->setAttributes(array_merge($conditions, $attributes));
        } else {
            // 存在则更新属性
            $model->setAttributes($attributes);
        }
        // 保存模型，并根据参数决定是否验证
        if ($model->save($validate)) {
            return $model;
        } else {
            throw new \yii\db\Exception(
                '保存模型失败：' . print_r($model->errors, true)
            );
        }
    }

    /**
     * 转换规则格式
     * @param array $rules
     * @return array
     */
    public static function transformRules(array $rules): array
    {
        $newRules = [];
        foreach ($rules as $rule){
            if (
                isset($rule[1]) &&
                in_array($rule[1],['integer','string','safe'])
            ){
                foreach ($rule[0] as $field){
                    $newRules[$field] = $rule[1];
                }
            }
        }
        return $newRules;
    }

    /**
     * 对象翻译方法实现
     * @param array|null $item 单挑数据
     * @param bool $transformRule 是否处理字段类型
     * @return array|null
     */
    public static function transform(?array $item, bool $transformRule = false): ?array
    {
        if (!$item){
            return $item;
        }
        $encodeConst = '::ENCODE_FIELD';
        $class       = static::class;
        $encodeMap = defined($class . $encodeConst) ? constant($class . $encodeConst) : [];
        $rules = ($transformRule && method_exists($class, 'fixedRules')) ? $class::fixedRules($item) : [];
        $rules && $rules = self::transformRules($rules);
        foreach ($item as $field => $value) {
            if (is_array($value)) {
                continue;
            }
            $upField = strtoupper($field);
            if (is_numeric($value) && $value < 0) {
                $value = sprintf('F%s', abs($value));
            }

            $slug    = sprintf('%s_%s', $upField, $value);
            $slugMap = sprintf('%s_MAP', $upField);
            // 判断当前类中是否存在该const的属性
            if (
                defined($class . '::' . $slug) &&
                defined($class . '::' . $slugMap)
            ) {
                $enum                         = constant($class . '::' . $slug);
                $map                          = constant($class . '::' . $slugMap);
                $item[sprintf('%s_', $field)] = $map[$enum] ?? null;
            }

            //时间翻译
            if (strpos($field, '_at') !== false) {
                if (is_numeric($value)) {
                    $item[sprintf('%s_', $field)] = $value > 0 ? date('Y-m-d H:i:s', $value) : '';
                }
            }

            // 内容脱敏
            if (isset($encodeMap[$field])) {
                list($start, $end, $len) = $encodeMap[$field];
                $item[$field] = strEncode($value, $start, $end, $len);
            }

            if (isset($rules[$field])){
                switch ($rules[$field]){
                    case 'string':
                        $item[$field] = (string) $value;
                        break;
                    case 'integer':
                        $item[$field] = (int) $value;
                        break;
                    case 'safe':
                        $item[$field] = json_decode($value,true);
                        break;
                }
            }
        }
        return $item;
    }

    /**
     * 获取一个模型的修改日志
     * @return string
     */
    public function getChangLog(): ?string
    {
        $dirty = $this->getDirtyAttributes();
        $desc  = [];
        if ($dirty) {
            $fields        = $this->attributeLabels();
            $newAttributes = self::transform($dirty);
            $oldAttributes = self::transform($this->getOldAttributes());
            foreach ($dirty as $key => $value) {
                if (isset($fields[$key])) {
                    $decodeKey = sprintf('%s_', $key);
                    $desc[]    = sprintf('%s：%s => %s', $fields[$key],
                        isset($oldAttributes[$decodeKey]) ? $oldAttributes[$decodeKey] : $oldAttributes[$key],
                        isset($newAttributes[$decodeKey]) ? $newAttributes[$decodeKey] : $newAttributes[$key]
                    );
                }
            }
        }
        $desc = implode('，', $desc);
        actionLogDesc($desc);
        return $desc;
    }

    public static function asField($field)
    {
        return sprintf('%s.%s',static::tableName(),$field);
    }


    public function toJson()
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    /**
     * 给模型绑定上当前登陆人的数据权限
     * @return void
     */
    public function bindCorp()
    {
        if ($this->hasAttribute('suite_id')){
            $this->suite_id = auth()->suiteId();
        }
        if ($this->hasAttribute('corp_id')){
            $this->corp_id = auth()->corpId();
        }
    }


}