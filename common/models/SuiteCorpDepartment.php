<?php

namespace common\models;

use common\models\concerns\traits\Corp;

/**
 * This is the model class for table "suite_corp_department".
 *
 * @property int $id 主键ID
 * @property string $suite_id 服务商ID
 * @property string $corp_id 企业ID
 * @property int $department_id 部门ID
 * @property int $order 在父部门中的次序值。order值大的排序靠前。值范围是[0, 2^32)
 * @property int $parentid 父部门id,根部门为1,与department_id字段相关
 * @property int $updated_at 创建时间
 * @property int $deleted_at 删除时间
 * @property string $path 部门路径
 */
class SuiteCorpDepartment extends \common\db\ActiveRecord
{
    use Corp;

    // 根部门
    const ROOT_DEPARTMENT_ID = 1;

    const CHANGE_FIELDS = ['suite_id', 'corp_id', 'department_id', 'order', 'parentid', 'updated_at', 'deleted_at', 'path'];

    public static function tableName()
    {
        return '{{suite_corp_department}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['suite_id', 'corp_id'], 'string', 'max' => 50],
            [['path'], 'string', 'max' => 500],
            [['department_id', 'order', 'parentid', 'updated_at', 'deleted_at'], 'integer'],
            [['department_id', 'order', 'parentid', 'updated_at', 'deleted_at'], 'default', 'value' => 0],
            [['suite_id', 'corp_id', 'path'], 'default', 'value' => '']
        ];
    }

    public function beforeValidate()
    {
        $this->updated_at = time();
        return parent::beforeValidate();
    }

    /**
     * 关联企业员工帐号所在部门表 查询企业负责人
     */
    public function getSuiteCorpAccountsDepartmentLeaders()
    {
        return $this->hasMany(SuiteCorpAccountsDepartment::className(), ['suite_id' => 'suite_id', 'corp_id' => 'corp_id', 'department_id' => 'department_id'])->where(['is_leader_in_dept' => SuiteCorpAccountsDepartment::IS_LEADER_IN_DEPT_1]);
    }

}