<?php

namespace common\models\crm;

use common\models\concerns\traits\Corp;
use common\models\concerns\traits\Helper;

/**
 * 企业的CRM客户联系人表
 * create table suite_corp_crm_customer_contact
 * (
 * @property int $id                int unsigned auto_increment comment '主键ID' primary key,
 * @property string $suite_id       varchar(50)      default ''  not null comment '服务商ID=suite_corp_config.suite_id',
 * @property string $corp_id        varchar(50)      default ''  not null comment '企业ID=suite_corp_config.corp_id',
 * @property string $customer_no    varchar(50)      default ''  not null comment '客户编号=suite_corp_crm_customer.customer_no',
 * @property string $contact_no     varchar(50)      default ''  not null comment '联系人编号',
 * @property string $contact_name   varchar(50)      default ''  not null comment '联系人姓名',
 * @property int $created_id        int              default 0   not null comment '创建人员工ID=suite_corp_accounts.id',
 * @property int $type              tinyint(3) unsigned default 1 not null comment '类型:1正常,2企业微信',
 * @property int $created_at        int(10) unsigned default '0' not null comment '创建时间',
 * @property int $updated_at        int(10) unsigned default '0' not null comment '更新时间',
 * @property int $deleted_at        int(10) unsigned default 0   not null comment '删除时间',
 * unique key uk_main (suite_id, corp_id, customer_no, contact_no),
 * KEY idx_created_at (created_at),
 * KEY idx_created_id (created_id)
 * )
 */
class SuiteCorpCrmCustomerContact extends \common\db\ActiveRecord
{
    use Helper;
    use Corp;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_crm_customer_contact';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['suite_id', 'corp_id', 'customer_no', 'contact_no','contact_name'], 'string', 'max' => 50],
            [['created_at', 'updated_at', 'created_id', 'deleted_at','type'], 'integer'],
            [['suite_id', 'corp_id', 'customer_no', 'contact_no'], 'unique', 'targetAttribute' => ['suite_id', 'corp_id', 'customer_no', 'contact_no']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'suite_id' => '服务商ID',
            'corp_id' => '企业ID',
            'customer_no' => '客户编号',
            'contact_no' => '联系人编号',
            'contact_name' => '联系人姓名',
            'created_id' => '创建人',
            'type' => '类型',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'deleted_at' => '删除时间',
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
     * 一对多:关联联系方式
     * @return \yii\db\ActiveQuery
     */
    public function getTags()
    {
        return $this->hasMany(SuiteCorpCrmCustomerContactTag::class, ['contact_no' => 'contact_no',]);
    }

    /**
     * 一对多:关联需求标签
     * @return \yii\db\ActiveQuery
     */
    public function getRequirementTags()
    {
        return $this->hasMany(SuiteCorpCrmCustomerRequirementTag::class,['suite_id' => 'suite_id','corp_id' => 'corp_id','customer_no' => 'customer_no','contact_no' => 'contact_no']);
    }

    /**
     * 一对多:关联联系方式
     * @return \yii\db\ActiveQuery
     */
    public function getInformation()
    {
        return $this->hasMany(SuiteCorpCrmCustomerContactInformation::class, ['contact_no' => 'contact_no',]);
    }

    /**
     * 一对一:关联创建人
     * @return \yii\db\ActiveQuery
     */
    public function getCreator()
    {
        return $this->hasOne(\common\models\Account::class, ['id' => 'created_id',]);
    }

    /**
     * 一对多:关联关系人
     * @return \yii\db\ActiveQuery
     */
    public function getLinks()
    {
        return $this->hasMany(SuiteCorpCrmCustomerLink::class, ['customer_no' => 'customer_no']);
    }

    /** @var int 类型:手动新建 */
    const TYPE_1 = 1;
    /** @var int 类型:企业微信 */
    const TYPE_2 = 2;
    /** @var string[] 类型 */
    const TYPE_MAP = [
        self::TYPE_1 => '手动新建',
        self::TYPE_2 => '企业微信',
    ];

}
