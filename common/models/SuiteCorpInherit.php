<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "suite_corp_inherit".
 *
 * @property int $id
 * @property string $suite_id 服务商ID
 * @property string $corp_id 企业ID
 * @property string $userid 交接人用户ID
 * @property string $heir 接收人用户ID
 * @property int $type 类型 1.在职继承 2.离职继承
 * @property int $status 执行状态 1.待执行 2.执行中 3.执行完毕
 * @property int $inherit_type 继承类型 1.继承客户 2.继承群 3.整体继承
 * @property string $create_userid 创建者用户ID
 * @property int $create_number 创建者工号
 * @property string $create_name 创建者姓名
 * @property int $complete_at 完成时间
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 */
class SuiteCorpInherit extends \yii\db\ActiveRecord
{

    // 类型 1.在职继承 2.离职继承
    const TYPE_1 = 1;
    const TYPE_2 = 2;

    const TYPE_DESC = [
        self::TYPE_1 => '在职继承',
        self::TYPE_2 => '离职继承',
    ];

    // 继承类型 1.继承客户 2.继承群 3.整体继承 4.客户及该客户相关群
    const INHERIT_TYPE_1 = 1;
    const INHERIT_TYPE_2 = 2;
    const INHERIT_TYPE_3 = 3;
    const INHERIT_TYPE_4 = 4;

    const INHERIT_TYPE_DESC = [
        self::INHERIT_TYPE_1 => '继承客户',
        self::INHERIT_TYPE_2 => '继承群',
        self::INHERIT_TYPE_3 => '整体继承',
        self::INHERIT_TYPE_4 => '客户及该客户相关群',
    ];

    // 执行状态 1.待执行 2.执行中 3.执行完毕
    const INHERIT_STATUS_1 = 1;
    const INHERIT_STATUS_2 = 2;
    const INHERIT_STATUS_3 = 3;

    const INHERIT_STATUS_DESC = [
        self::INHERIT_STATUS_1 => '待执行',
        self::INHERIT_STATUS_2 => '执行中',
        self::INHERIT_STATUS_3 => '执行完毕',
    ];

    const CHANGE_FIELDS = ['suite_id', 'corp_id', 'userid', 'heir', 'type', 'status', 'inherit_type', 'create_userid', 'create_number', 'create_name', 'complete_at', 'created_at', 'updated_at'];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_inherit';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'status', 'inherit_type', 'create_number', 'complete_at', 'created_at', 'updated_at'], 'integer'],
            [['type', 'inherit_type', 'create_number', 'complete_at', 'created_at', 'updated_at'], 'default', 'value' => 0],
            [['status'], 'default', 'value' => self::INHERIT_STATUS_1],
            [['suite_id', 'corp_id', 'userid', 'heir', 'create_userid', 'create_name'], 'string', 'max' => 50],
            [['suite_id', 'corp_id', 'userid', 'heir', 'create_userid', 'create_name'], 'default', 'value' => ''],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'            => 'ID',
            'suite_id'      => 'Suite ID',
            'corp_id'       => 'Corp ID',
            'userid'        => 'Userid',
            'heir'          => 'Heir',
            'type'          => 'Type',
            'status'        => 'Status',
            'inherit_type'  => 'Inherit Type',
            'create_userid' => 'Create Userid',
            'create_number' => 'Create Number',
            'create_name'   => 'Create Name',
            'complete_at'   => 'Complete At',
            'created_at'    => 'Created At',
            'updated_at'    => 'Updated At',
        ];
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        $time = time();
        if ($this->isNewRecord) {
            $this->created_at = $time;
        }
        $this->updated_at = $time;
        return parent::beforeValidate();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAccountsDepartmentByUserId()
    {
        return $this->hasMany(SuiteCorpAccountsDepartment::class, ['suite_id' => 'suite_id', 'corp_id' => 'corp_id', 'userid' => 'userid']);
    }

    /**
     * 交接人信息
     * @return \yii\db\ActiveQuery
     */
    public function getAccountsByUserId()
    {
        return $this->hasOne(Account::class, ['suite_id' => 'suite_id', 'corp_id' => 'corp_id', 'userid' => 'userid'])->select('suite_id,corp_id,userid,jnumber,nickname');
    }

    /**
     * 接收人信息
     * @return \yii\db\ActiveQuery
     */
    public function getAccountsByHeir()
    {
        return $this->hasOne(Account::class, ['suite_id' => 'suite_id', 'corp_id' => 'corp_id', 'userid' => 'heir'])->select('suite_id,corp_id,userid,jnumber,nickname');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getInheritListCountById()
    {
        return $this->hasMany(SuiteCorpInheritList::class, ['inherit_id' => 'id'])->select(
            [
                'inherit_id',
                'type',
                'count(id) as counts',
                'count(if(status=1,1,null)) as success_counts',
            ]
        )->groupBy('inherit_id,type');
    }

}
