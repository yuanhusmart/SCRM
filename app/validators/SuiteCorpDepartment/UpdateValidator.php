<?php

namespace app\validators\SuiteCorpDepartment;

use common\helpers\HasVariables;
use common\helpers\validator\Validator;
use common\models\SuiteCorpDepartment;

/**
 * @property int $id
 */
class UpdateValidator extends Validator
{
    use HasVariables;


    public function rules()
    {
        return [
            ['id', 'required'],
            ['id', 'validateExist'],
            ['name', 'validateNameUnique'],
        ];
    }

    public function validateExist($attribute, $params)
    {
        $department = $this->department();
        if ($department === null) {
            $this->addError($attribute, '部门不存在。');
        }
    }

    public function validateNameUnique($attribute, $params)
    {
        $department = $this->department();

        if (!$department) {
            return;
        }

        // 重复

        $duplicate = SuiteCorpDepartment::find()
            ->andWhere(['name' => $this->$attribute])
            ->andWhere(['!=', 'id', $this->id])
            ->andWhere(['corp_id' => $department->corp_id])
            ->one();

        if ($duplicate !== null) {
            $this->addError($attribute, '部门名称已存在。');
        }
    }


    /**
     * @return SuiteCorpDepartment
     */
    public function department()
    {
        return $this->rememberVariable('department', function () {
            return SuiteCorpDepartment::findOne($this->id);
        });
    }
}