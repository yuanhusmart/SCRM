<?php

namespace app\validators\SuiteCorpAccount;

use common\helpers\validator\Validator;
use common\models\SuiteCorpAccount;

class UpdateValidator extends Validator
{
    public function rules()
    {
        return [
            ['id', 'required'],
            ['id', 'validateExist'],
            ['mobile', 'validateMobileUnique'],
        ];
    }

    public function validateExist($attribute, $params)
    {
        $account = SuiteCorpAccount::findOne($this->$attribute);
        if ($account === null) {
            $this->addError($attribute, '员工不存在。');
        }
    }

    public function validateMobileUnique($attribute, $params)
    {
        if (empty($this->$attribute)) {
            return;
        }

        $corpId = $this->corp_id ?? auth()->corpId();

        $account = SuiteCorpAccount::find()
            ->andWhere(['mobile' => $this->$attribute])
            ->andWhere(['corp_id' => $corpId])
            ->andWhere(['<>', 'id', $this->id])
            ->one();

        if ($account) {
            $this->addError($attribute, '手机号已存在。');
        }
    }
}
