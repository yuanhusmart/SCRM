<?php

namespace app\validators\SuiteCorpAccount;

use common\helpers\validator\Validator;
use common\models\SuiteCorpAccount;
use common\models\SuiteCorpDepartment;

class CreateValidator extends Validator
{
    public function rules()
    {
        return [
            ['name', 'required'],
            ['mobile', 'required'],
            ['mobile', 'validateMobileUnique'],
        ];
    }

    public function validateMobileUnique($attribute, $params)
    {
        $corpId = $this->corp_id ?? auth()->corpId();

        $account = SuiteCorpAccount::find()
            ->andWhere(['mobile' => $this->$attribute])
            ->andWhere(['corp_id' => $corpId])
            ->one();

        if ($account) {
            $this->addError('mobile-duplicate', "该手机号和{$account->name}的已重复，是否清空{$account->name}的手机号。");
        }
    }
}
