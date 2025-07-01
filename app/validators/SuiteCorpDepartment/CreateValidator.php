<?php

namespace app\validators\SuiteCorpDepartment;

use common\helpers\validator\Validator;
use common\models\SuiteCorpDepartment;

class CreateValidator extends Validator
{
    public function rules()
    {
        return [
            ['name', 'required'],
            ['name', 'validateNameUnique'],
        ];
    }

    public function validateNameUnique($attribute, $params)
    {
        $corpId = $this->corp_id ?? auth()->corpId();

        $department = SuiteCorpDepartment::find()
            ->andWhere(['name' => $this->$attribute])
            ->andWhere(['corp_id' => $corpId])
            ->one();
        
        if ($department !== null) {
            $this->addError($attribute, '部门名称已存在。');
        }
    }
}