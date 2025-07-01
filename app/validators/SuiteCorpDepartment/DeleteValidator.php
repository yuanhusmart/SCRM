<?php

namespace app\validators\SuiteCorpDepartment;

use common\models\concerns\enums\SuiteCorpAccount\Status;
use common\models\SuiteCorpAccountsDepartment;
use common\models\SuiteCorpDepartment;
use common\models\Account;

class DeleteValidator extends UpdateValidator
{
    public function rules()
    {
        return [
            ['id', 'required'],
            ['id', 'validateExist'],
            ['id', 'validateHasChildren'],
            ['id', 'validateHasAccount'],
        ];
    }

    /**
     * 检查是否有子部门
     * @param $attribute
     * @param $params
     * @return void
     */
    public function validateHasChildren($attribute, $params)
    {
        if (!$department = $this->department()) {
            return;
        }

        $children = SuiteCorpDepartment::find()
            ->andWhere(['parent_id' => $department->id])
            ->one();

        if ($children !== null) {
            $this->addError($attribute, '部门下有子部门，不能删除。');
        }
    }

    public function validateHasAccount($attribute, $params)
    {
        if (!$department = $this->department()) {
            return;
        }

        $accounts = SuiteCorpAccountsDepartment::find()
            ->andWhere(['department_id' => $department->id])
            ->andWhere(['!=', 'is_leader_in_dept', SuiteCorpAccountsDepartment::IS_LEADER_IN_DEPT_1])
            // 需要查询帐号状态不为离职的数据
            ->andWhere([
                'exists',
                Account::find()
                    ->andWhere(['!=', 'status', Status::RESIGNED])
                    ->andWhere('suite_corp_accounts_department.account_id=suite_corp_accounts.id'),
            ])
            ->one();

        if ($accounts !== null) {
            $this->addError($attribute, '部门下有成员，不能删除。');
        }
    }
}