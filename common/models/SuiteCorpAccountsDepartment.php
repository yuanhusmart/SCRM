<?php

namespace common\models;

use common\models\concerns\traits\CorpNotSoft;

/**
 * This is the model class for table "suite_corp_accounts_department".
 *
 * @property int $id 主键ID
 * @property string $suite_id 服务商ID
 * @property string $corp_id 企业ID
 * @property int $department_id 部门ID
 * @property string $userid 微信userid
 * @property int $is_leader_in_dept 表示在所在的部门内是否为部门负责人。0-否；1-是
 */
class SuiteCorpAccountsDepartment extends \common\db\ActiveRecord
{
    use CorpNotSoft;

    const CHANGE_FIELDS = ['suite_id', 'corp_id', 'department_id', 'userid', 'is_leader_in_dept'];

    /** @var int 表示在所在的部门内是否为部门负责人:否 */
    const IS_LEADER_IN_DEPT_0 = 0;
    /** @var int 表示在所在的部门内是否为部门负责人:是 */
    const IS_LEADER_IN_DEPT_1 = 1;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_accounts_department';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['department_id', 'is_leader_in_dept'], 'integer'],
            [['suite_id', 'corp_id'], 'string', 'max' => 50],
            [['userid'], 'string', 'max' => 100],
            [['suite_id', 'corp_id', 'department_id', 'userid'], 'unique', 'targetAttribute' => ['suite_id', 'corp_id', 'department_id', 'userid']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'                => 'ID',
            'suite_id'          => 'Suite ID',
            'corp_id'           => 'Corp ID',
            'department_id'     => 'Department ID',
            'userid'            => 'Userid',
            'is_leader_in_dept' => 'Is Leader In Dept',
        ];
    }

    /**
     * 关联 企业部门表
     * @return \yii\db\ActiveQuery
     */
    public function getDepartmentByAccountsDepartment()
    {
        return $this->hasOne(SuiteCorpDepartment::class, ['suite_id' => 'suite_id', 'corp_id' => 'corp_id', 'department_id' => 'department_id'])->select('suite_id,corp_id,department_id,path');
    }

    /**
     * 关联帐号表
     * @return \yii\db\ActiveQuery
     */
    public function getAccountByDepartmentLeader()
    {
        return $this->hasOne(Account::className(), ['suite_id' => 'suite_id', 'corp_id' => 'corp_id', 'userid' => 'userid'])->select('suite_id,corp_id,userid,nickname');
    }
}
