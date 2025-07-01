<?php

namespace app\validators\SuitePermission;

use common\helpers\validator\Validator;
use common\models\SuitePermission;

class UpdateValidator extends Validator
{
    public function rules()
    {
        return [
            ['id', 'required'],
            ['name', 'validateNameUnique'],
            ['slug', 'validateSlugUnique'],
        ];
    }

    public function validateSlugUnique($attribute, $params)
    {
        $slug = $this->$attribute;

        $model = SuitePermission::find()
            ->andWhere(['slug' => $slug])
            ->andWhere(['<>', 'id', $this->id])
            ->one();

        if ($model) {
            $this->addError($attribute, '标识已存在');
        }
    }

    public function validateNameUnique($attribute, $params)
    {
        $name     = $this->$attribute;
        $parentId = $this->parent_id;

        $model = SuitePermission::find()
            ->andWhere(['name' => $name])
            ->andWhere(['parent_id' => $parentId])
            ->andWhere(['<>', 'id', $this->id])
            ->one();

        if ($model) {
            $this->addError($attribute, '名称已存在');
        }
    }
}

