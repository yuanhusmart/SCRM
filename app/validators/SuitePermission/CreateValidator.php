<?php

namespace app\validators\SuitePermission;

use common\helpers\validator\Validator;
use common\models\SuitePermission;

class CreateValidator extends Validator
{
    public function rules()
    {
        return [
            ['type', 'required'],
            ['parent_id', 'required'],
            ['name', 'required'],
            ['name', 'validateNameUnique'],
            ['slug', 'required'],
            ['slug', 'validateSlugUnique'],
            ['level', 'required'],
            ['is_hide', 'required'],
            ['status', 'required'],
        ];
    }

    public function validateSlugUnique($attribute, $params)
    {
        $slug = $this->$attribute;

        $model = SuitePermission::find()->where(['slug' => $slug])->one();
        if ($model) {
            $this->addError($attribute, '标识已存在');
        }
    }

    public function validateNameUnique($attribute, $params)
    {
        $name     = $this->$attribute;
        $parentId = $this->parent_id;

        $model = SuitePermission::find()->where([
            'name'      => $name,
            'parent_id' => $parentId,
        ])->one();

        if ($model) {
            $this->addError($attribute, '名称已存在');
        }
    }
}

