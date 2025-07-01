<?php

namespace app\validators\SuitePackage;

use common\helpers\validator\Validator;
use common\models\SuitePackage;

class UpdateValidator extends Validator
{
    public function rules()
    {
        return [
            ['id', 'required'],
            ['name', 'validateNameUnique'],
        ];
    }

    public function validateNameUnique($attribute, $params)
    {
        $package = SuitePackage::find()
            ->andWhere(['name' => $this->$attribute])
            ->andWhere(['<>', 'id', $this->id])
            ->one();

        if ($package) {
            $this->addError($attribute, '套餐名称已存在');
        }
    }
}