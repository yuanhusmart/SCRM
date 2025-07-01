<?php

namespace app\validators\SuiteRole;

use common\helpers\validator\Validator;
use common\models\SuiteRole;

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
        $name    = $this->$attribute;
        $suiteId = input('suite_id', auth()->suiteId());
        $corpId  = input('corp_id', auth()->corpId());
        $kind    = input('kind', 1);

        $model = SuiteRole::find()
            ->andWhere([
                'name'     => $name,
                'suite_id' => $suiteId,
                'corp_id'  => $corpId,
                'kind'     => $kind
            ])
            ->one();

        if ($model) {
            $this->addError($attribute, '角色名已存在');
        }
    }

}