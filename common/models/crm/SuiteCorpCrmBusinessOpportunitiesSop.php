<?php

namespace common\models\crm;

use common\models\concerns\traits\CorpNotSoft;
use common\models\SuiteCorpConfig;

/**
 * 企业的CRM商机SOP表
 * create table suite_corp_crm_business_opportunities_sop
 * (
 * @property int $id                           int unsigned auto_increment comment '主键ID' primary key,
 * @property string $suite_id                  varchar(50)      default '' not null comment '服务商ID=suite_corp_config.suite_id',
 * @property string $corp_id                   varchar(50)      default ''  not null comment '企业ID=suite_corp_config.corp_id',
 * @property string $customer_no               varchar(50)      default ''  not null comment '客户编号=suite_corp_crm_customer.customer_no',
 * @property string $business_opportunities_no varchar(50)      default ''  not null comment '商机编号=suite_corp_crm_business_opportunities.business_opportunities_no',
 * @property json|null $content                json default null comment 'sop阶段配置',
 * @property int $created_at                   int(10) unsigned default '0' not null comment '创建时间',
 * @property int $updated_at                   int(10) unsigned default '0' not null comment '更新时间',
 * KEY idx_corp_customer (suite_id, corp_id, customer_no) COMMENT '常规列表查询',
 * KEY idx_business_opportunities_no (business_opportunities_no) COMMENT '关联查询'
 * )
 */
class SuiteCorpCrmBusinessOpportunitiesSop extends \common\db\ActiveRecord
{
    use CorpNotSoft;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_crm_business_opportunities_sop';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'updated_at'], 'integer'],
            [['suite_id', 'corp_id', 'customer_no', 'business_opportunities_no',], 'string', 'max' => 50],
            [['content'], 'safe', 'skipOnEmpty' => true],
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
            'content' => 'sop阶段配置',
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
        return $this->hasOne(SuiteCorpConfig::class, ['corp_id' => 'corp_id']);
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
}