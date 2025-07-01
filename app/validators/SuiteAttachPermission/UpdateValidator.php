<?php

namespace app\validators\SuiteAttachPermission;

use common\helpers\validator\Validator;

class UpdateValidator extends Validator
{
    public function rules()
    {
        return [
            ['id', 'required'],
        ];
    }
}