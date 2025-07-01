<?php

namespace common\models\crm;

use common\models\concerns\traits\CorpNotSoft;

/**
 * 企业的CRM商机关系人表
 * create table suite_corp_crm_business_opportunities_link
 * (
 * @property int $id                                    int unsigned auto_increment comment '主键ID' primary key,
 * @property string $suite_id                           varchar(50)  default '' not null comment '服务商ID=suite_corp_config.suite_id',
 * @property string $corp_id                            varchar(50)      default ''  not null comment '企业ID=suite_corp_config.corp_id',
 * @property string $customer_no                        varchar(50)      default ''  not null comment '客户编号=suite_corp_crm_customer.customer_no',
 * @property string $business_opportunities_no          varchar(50)      default ''  not null comment '商机编号=suite_corp_crm_business_opportunities.business_opportunities_no',
 * @property string $business_opportunities_link_no     varchar(50)      default ''  not null comment '商机关系人编号',
 * @property int $relational                            tinyint(3)       default 1   not null comment '关系:1跟进人,2协作人',
 * @property int $account_id                            int              default 0   not null comment '员工ID=suite_corp_accounts.id',
 * @property int $created_at                            int(10) unsigned default '0' not null comment '创建时间',
 * @property int $updated_at                            int(10) unsigned default '0' not null comment '更新时间',
 * UNIQUE KEY uk_no (business_opportunities_link_no),
 * KEY idx_main (suite_id, corp_id, customer_no,business_opportunities_no,relational),
 * KEY idx_account_id(account_id)
 * )
 */
class SuiteCorpCrmBusinessOpportunitiesLink extends \common\db\ActiveRecord
{
    use CorpNotSoft;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_crm_business_opportunities_link';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'updated_at', 'relational', 'account_id'], 'integer'],
            [['suite_id', 'corp_id', 'customer_no', 'business_opportunities_no', 'business_opportunities_link_no'], 'string', 'max' => 50],
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
            'business_opportunities_link_no' => '商机关系人编号',
            'relational' => '关系',
            'account_id' => '员工ID',
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
     * 一对一:关联商机
     * @return \yii\db\ActiveQuery
     */
    public function getBusinessOpportunities()
    {
        return $this->hasOne(SuiteCorpCrmBusinessOpportunities::class, ['business_opportunities_no' => 'business_opportunities_no']);
    }

    /**
     * 一对一:关联员工
     * @return \yii\db\ActiveQuery
     */
    public function getAccount()
    {
        return $this->hasOne(\common\models\Account::class, ['id' => 'account_id']);
    }

    /** @var int 关系人:跟进人 */
    const RELATIONAL_1 = 1;
    /** @var int 关系人:协作人 */
    const RELATIONAL_2 = 2;
    /** @var array 关系人 */
    const RELATIONAL_MAP = [
        self::RELATIONAL_1 => '跟进人',
        self::RELATIONAL_2 => '协作人',
    ];
}
