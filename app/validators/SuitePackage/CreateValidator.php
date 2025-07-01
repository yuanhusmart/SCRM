<?php

namespace app\validators\SuitePackage;

use common\helpers\validator\Validator;
use common\models\SuitePackage;

class CreateValidator extends Validator
{
    public function rules()
    {
        return [
            ['type', 'required'],
            ['name', 'required'],
            ['description', 'required'],
            ['name', 'validateNameUnique'],
        ];
    }

    public function validateNameUnique($attribute, $params)
    {
        $package = SuitePackage::find()
            ->andWhere(['name' => $this->$attribute])
            ->one();

        if ($package) {
            $this->addError($attribute, '套餐名称已存在');
        }
    }
}

