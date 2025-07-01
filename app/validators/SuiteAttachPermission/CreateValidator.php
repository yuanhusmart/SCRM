<?php

namespace app\validators\SuiteAttachPermission;

use common\helpers\validator\Validator;

class CreateValidator extends Validator
{
    public function rules()
    {
        return [
            ['type', 'required'],
            ['account_id', 'required'],
            ['time_type', 'required'],
        ];
    }
}