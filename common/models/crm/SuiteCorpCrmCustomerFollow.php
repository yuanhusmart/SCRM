<?php

namespace common\models\crm;

use common\models\concerns\traits\CorpNotSoft;

/**
 * 企业的CRM客户跟进表
 * create table suite_corp_crm_customer_follow
 * (
 * @property int $id                           int unsigned auto_increment comment '主键ID' primary key,
 * @property string $suite_id                  varchar(50)      default ''  not null comment '服务商ID=suite_corp_config.suite_id',
 * @property string $corp_id                   varchar(50)      default ''  not null comment '企业ID=suite_corp_config.corp_id',
 * @property string $customer_no               varchar(50)      default ''  not null comment '客户编号=suite_corp_crm_customer.customer_no',
 * @property string $business_opportunities_no varchar(50)      default ''  not null comment '商机编号=suite_corp_crm_business_opportunities.business_opportunities_no',
 * @property string $contact_no                varchar(50)      default ''  not null comment '联系人编号=suite_corp_crm_customer_contact.contact_no',
 * @property string $follow_no                 varchar(50)      default ''  not null comment '跟进编号',
 * @property int $created_id                   int              default 0   not null comment '创建人员工ID=suite_corp_accounts.id',
 * @property int $follow_type                  tinyint(3)       default 1   not null comment '跟进方式:1人工,2AI',
 * @property string $content                   TEXT             default null comment '跟进内容/AI汇总内容',
 * @property json|null $changed                json             default null comment '自动变更数据明细',
 * @property int $created_at                   int(10) unsigned default '0' not null comment '创建时间',
 * @property int $updated_at                   int(10) unsigned default '0' not null comment '更新时间',
 * unique key uk_no (follow_no),
 * KEY idx_customer_no (suite_id, corp_id, customer_no),
 * KEY idx_business_opportunities_no (suite_id, corp_id, business_opportunities_no),
 * KEY idx_contact_no (suite_id, corp_id, contact_no),
 * KEY idx_created_at (created_at)
 * )
 */
class SuiteCorpCrmCustomerFollow extends \common\db\ActiveRecord
{
    use CorpNotSoft;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_crm_customer_follow';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['suite_id', 'corp_id', 'customer_no', 'business_opportunities_no', 'contact_no', 'follow_no'], 'string', 'max' => 50],
            [['created_at', 'updated_at', 'created_id', 'follow_type'], 'integer'],
            [['changed'], 'safe', 'skipOnEmpty' => true],
            [['content'], 'string'],
            [['follow_no'], 'unique', 'targetAttribute' => ['follow_no']],
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
            'follow_no' => '跟进编号',
            'created_id' => '创建人员工',
            'follow_type' => '跟进方式',
            'content' => '跟进内容',
            'changed' => '自动变更数据明细',
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
     * 一对一:关联创建人
     * @return \yii\db\ActiveQuery
     */
    public function getCreator()
    {
        return $this->hasOne(\common\models\Account::class, ['id' => 'created_id',]);
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
        return $this->hasOne(SuiteCorpCrmCustomerContact::class,
            [
                'suite_id' => 'suite_id',
                'corp_id' => 'corp_id',
                'customer_no' => 'customer_no',
                'contact_no' => 'contact_no'
            ])
            ->andWhere([SuiteCorpCrmCustomerContact::asField('deleted_at') => 0,]);
    }

    /**
     * 一对一:关联商机
     * @return \yii\db\ActiveQuery
     */
    public function getBusinessOpportunities()
    {
        return $this->hasOne(SuiteCorpCrmBusinessOpportunities::class, ['business_opportunities_no' => 'business_opportunities_no']);
    }

    /** @var int 跟进方式: 人工 */
    const FOLLOW_TYPE_1 = 1;
    /** @var int 跟进方式: AI */
    const FOLLOW_TYPE_2 = 2;
    /** @var array 跟进方式 */
    const FOLLOW_TYPE_MAP = [
        self::FOLLOW_TYPE_1 => '人工',
        self::FOLLOW_TYPE_2 => 'AI',
    ];

}
