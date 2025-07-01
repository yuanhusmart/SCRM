<?php

namespace app\validators\SuitePermission;

use common\helpers\validator\Validator;
use common\models\SuitePermission;

class DeleteValidator extends Validator
{
    public function rules()
    {
        return [
            ['id', 'required'],
            ['id', 'validateHaveChildren'],
        ];
    }

    public function validateHaveChildren($attribute, $params)
    {
        $id    = $this->$attribute;
        $model = SuitePermission::find()->where(['parent_id' => $id])->one();

        if ($model) {
            $this->addError($attribute, '请先删除子权限');
        }
    }
}