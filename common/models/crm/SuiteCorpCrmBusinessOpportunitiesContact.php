<?php

namespace common\models\crm;

use common\models\concerns\traits\CorpNotSoft;

/**
 * 企业的CRM商机联系人表
 * create table suite_corp_crm_business_opportunities_contact
 * (
 * @property int $id                                    int unsigned auto_increment comment '主键ID' primary key,
 * @property string $suite_id                           varchar(50)      default '' not null comment '服务商ID=suite_corp_config.suite_id',
 * @property string $corp_id                            varchar(50)      default ''  not null comment '企业ID=suite_corp_config.corp_id',
 * @property string $customer_no                        varchar(50)      default ''  not null comment '客户编号=suite_corp_crm_customer.customer_no',
 * @property string $business_opportunities_no          varchar(50)      default ''  not null comment '商机编号=suite_corp_crm_business_opportunities.business_opportunities_no',
 * @property string $contact_no                         varchar(50)      default ''  not null comment '联系人编号=suite_corp_crm_customer_contact.contact_no',
 * @property string $role                               varchar(50)      default ''  not null comment '在商机中担任的角色(中文冗余)',
 * @property int $is_main                               tinyint(3)       default 1   not null comment '是否主要联系人:1是,2否',
 * @property int $created_at                            int(10) unsigned default '0' not null comment '创建时间',
 * @property int $updated_at                            int(10) unsigned default '0' not null comment '更新时间',
 * unique key uk_main (suite_id, corp_id,customer_no,business_opportunities_no,contact_no,role),
 * KEY idx_contact_no (contact_no),
 * KEY idx_business_opportunities_no (business_opportunities_no)
 * )
 */
class SuiteCorpCrmBusinessOpportunitiesContact extends \common\db\ActiveRecord
{
    use CorpNotSoft;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_crm_business_opportunities_contact';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'updated_at', 'is_main'], 'integer'],
            [['suite_id', 'corp_id', 'customer_no', 'business_opportunities_no', 'contact_no','role'], 'string', 'max' => 50],
            [['suite_id', 'corp_id', 'customer_no', 'business_opportunities_no', 'contact_no','role'], 'unique', 'targetAttribute' => ['suite_id', 'corp_id', 'customer_no', 'business_opportunities_no', 'contact_no','role']],
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
            'business_opportunities_no' => '商机编号',
            'contact_no' => '联系人编号',
            'role' => '角色',
            'is_main' => '是否主要联系人',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
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
     * 一对一:关联企业
     * @return \yii\db\ActiveQuery
     */
    public function getCorp()
    {
        return $this->hasOne(\common\models\SuiteCorpConfig::class, ['corp_id' => 'corp_id']);
    }

    /**
     * 一对一:关联客户
     * @return \yii\db\ActiveQuery
     */
    public function getCustomer()
    {
        return $this->hasOne(SuiteCorpCrmCustomer::class, ['customer_no' => 'customer_no'])->andWhere([SuiteCorpCrmCustomer::asField('deleted_at') => 0,]);
    }

    /**
     * 一对一:关联联系人
     * @return \yii\db\ActiveQuery
     */
    public function getContact()
    {
        return $this->hasOne(SuiteCorpCrmCustomerContact::class, ['suite_id' => 'suite_id', 'corp_id' => 'corp_id', 'customer_no' => 'customer_no', 'contact_no' => 'contact_no'])->andWhere([SuiteCorpCrmCustomerContact::asField('deleted_at') => 0,]);
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
     * 一对一:关联商机
     * @return \yii\db\ActiveQuery
     */
    public function getBusinessOpportunities()
    {
        return $this->hasOne(SuiteCorpCrmBusinessOpportunities::class, ['business_opportunities_no' => 'business_opportunities_no']);
    }

    /** @var int 是否主要联系人:是 */
    const IS_MAIN_1 = 1;
    /** @var int 是否主要联系人:否 */
    const IS_MAIN_2 = 2;
    /** @var string[] 是否主要联系人 */
    const IS_MAIN_MAP = [
        self::IS_MAIN_1 => '是',
        self::IS_MAIN_2 => '否',
    ];
}
