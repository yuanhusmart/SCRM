<?php

namespace common\helpers\validator;

use yii\base\DynamicModel;
use yii\base\InvalidConfigException;
use yii\validators\Validator as BaseValidator;

/**
 * 数据验证器超类
 *
 * @example
 *
 * 1. 按照对应控制器方法声明一个子类继承该类, 例如 StaffController::actionCreate, 其他用法跟 model 的校验用法一致
 * class CreateValidator extends Validator
 * {
 *     public function rules()
 *     {
 *         return [
 *            ['name', 'required']
 *         ];
 *     }
 * }
 *
 * 2. 在 StaffController::actionCreate 中使用, 验证失败会直接抛出异常
 * $params = $this->getBodyParams();
 * CreateValidator::validateData($params);
 */
class Validator extends DynamicModel
{

    public function rules()
    {
        return [];
    }

    /**
     * 数据校验
     * @param array $data 要校验的数据
     * @param array $rules 临时要校验的规则
     * @return static
     */
    public static function validateData(array $data, $rules = null)
    {
        $model = new static();

        // 如果入参有$rules, 就使用入参的rules
        // 否则就使用类中 rules() 方法返回的的数据
        if (!empty($rules)) {
            $validators = $model->getValidators();
            foreach ($rules as $rule) {
                if ($rule instanceof BaseValidator) {
                    $validators->append($rule);
                } elseif (is_array($rule) && isset($rule[0], $rule[1])) { // attributes, validator type
                    $validator = BaseValidator::createValidator($rule[1], $model, (array)$rule[0], array_slice($rule, 2));
                    $validators->append($validator);
                } else {
                    throw new InvalidConfigException('Invalid validation rule: a rule must specify both attribute names and validator type.');
                }
            }
        } else {
            $rules = $model->rules();
        }

        $model->_setAttributes(
            array_merge(
                $model->_ruleToAttribute($rules),
                $data
            )
        );

        if ($model->validate()) {
            return $model;
        }

        $errors     = $model->errors;
        $firstError = array_shift($errors)[0];

        throw new ValidationException($firstError, $errors);
    }


    protected function _setAttributes(array $attributes)
    {
        foreach ($attributes as $name => $value) {
            $this->defineAttribute($name, $value);
        }
    }

    protected function _ruleToAttribute($rules)
    {
        $attributes = [];

        foreach ((array)$rules as $rule) {
            $keys = (array)array_shift($rule);

            foreach ($keys as $key) {
                $attributes[$key] = null;
            }
        }

        return $attributes;
    }
}