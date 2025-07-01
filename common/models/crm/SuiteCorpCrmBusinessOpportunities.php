<?php

namespace common\models\crm;

use common\models\concerns\traits\CorpNotSoft;

/**
 * 企业的CRM商机表
 * create table suite_corp_crm_business_opportunities
 * (
 * @property int $id                           int unsigned auto_increment comment '主键ID' primary key,
 * @property string $suite_id                  varchar(50)      default ''  not null comment '服务商ID=suite_corp_config.suite_id',
 * @property string $corp_id                   varchar(50)      default ''  not null comment '企业ID=suite_corp_config.corp_id',
 * @property string $customer_no               varchar(50)      default ''  not null comment '客户编号=suite_corp_crm_customer.customer_no',
 * @property string $industry_no               varchar(50)    default ''   not null comment '行业编号=suite_corp_industry.industry_no',
 * @property int $is_analyze                   tinyint(3)       default 1   not null comment '是否分析:1是,2否',
 * @property string $business_opportunities_no varchar(50)      default ''  not null comment '商机编号',
 * @property string $level                     varchar(10)      default ''  not null comment '商机等级',
 * @property string $name                      varchar(50)      default ''  not null comment '商机名称',
 * @property float $estimate_sale_money        decimal(12,2) default 0.00 not null comment '预计销售金额',
 * @property float $order_money                decimal(12,2) default 0.00 not null comment '已回款金额',
 * @property float $deal_rate                  decimal(12,2) default 0.00 not null comment '成交几率(百分比)',
 * @property int $status                       tinyint(3)       default 1   not null comment '商机状态:1沟通中,2已成交,3已作废',
 * @property int $loss_risk                    tinyint(3)       default 1   not null comment '流失风险:1未知,2低,3中,4高',
 * @property int $source                       tinyint(3)       default 1   not null comment '商机来源:1搜索引擎,2广告,3转介绍,4线上咨询,5上门咨询,6其他',
 * @property string $remark                    varchar(255)     default ''  not null comment '备注',
 * @property int $created_id                   int              default 0   not null comment '创建人员工ID=suite_corp_accounts.id',
 * @property int $created_at                   int(10) unsigned default '0' not null comment '创建时间',
 * @property int $updated_at                   int(10) unsigned default '0' not null comment '更新时间',
 * @property int $deal_at                      int(10) unsigned default '0' not null comment '成交时间',
 * @property int $last_follow_at               int(10) unsigned default '0' not null comment '最近跟进时间=last suite_corp_crm_customer_follow.created_at',
 * unique key uk_no (business_opportunities_no),
 * KEY idx_corp_customer (suite_id, corp_id, customer_no),
 * KEY idx_industry_no (industry_no),
 * KEY idx_sort(status,last_follow_at,created_at)
 * )
 */
class SuiteCorpCrmBusinessOpportunities extends \common\db\ActiveRecord
{
    use CorpNotSoft;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_crm_business_opportunities';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'updated_at', 'status', 'loss_risk', 'source', 'deal_at', 'last_follow_at', 'created_id', 'is_analyze'], 'integer'],
            [['industry_no', 'suite_id', 'corp_id', 'customer_no', 'business_opportunities_no', 'name'], 'string', 'max' => 50],
            [['level'], 'string', 'max' => 10],
            [['remark'], 'string', 'max' => 255],
            [['estimate_sale_money', 'order_money', 'deal_rate'], 'number', 'min' => 0, 'max' => 999999999999.99],
            [['business_opportunities_no'], 'unique', 'targetAttribute' => ['business_opportunities_no']],
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
            'industry_no' => '行业编号',
            'is_analyze' => '是否分析',
            'business_opportunities_no' => '商机编号',
            'level' => '等级',
            'name' => '名称',
            'estimate_sale_money' => '预计销售金额',
            'order_money' => '已回款金额',
            'deal_rate' => '成交几率',
            'status' => '状态',
            'loss_risk' => '流失风险',
            'source' => '来源',
            'remark' => '备注',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'deal_at' => '成交时间',
            'last_follow_at' => '最近跟进时间',
            'created_id' => '创建人',
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
     * 一对一:关联行业
     * @return \yii\db\ActiveQuery
     */
    public function getIndustry()
    {
        return $this->hasOne(\common\models\SuiteCorpIndustry::class, ['industry_no' => 'industry_no']);
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
     * 一对多:关联联系人
     * @return \yii\db\ActiveQuery
     */
    public function getBusinessOpportunitiesContacts()
    {
        return $this->hasMany(SuiteCorpCrmBusinessOpportunitiesContact::class, ['business_opportunities_no' => 'business_opportunities_no']);
    }

    /**
     * 一对多:关联关系人
     * @return \yii\db\ActiveQuery
     */
    public function getBusinessOpportunitiesLinks()
    {
        return $this->hasMany(SuiteCorpCrmBusinessOpportunitiesLink::class, ['business_opportunities_no' => 'business_opportunities_no']);
    }

    /**
     * 一对一:关联SOP
     * @return \yii\db\ActiveQuery
     */
    public function getSop()
    {
        return $this->hasOne(SuiteCorpCrmBusinessOpportunitiesSop::class, ['business_opportunities_no' => 'business_opportunities_no']);
    }

    /**
     * 一对一:关联创建人
     * @return \yii\db\ActiveQuery
     */
    public function getCreator()
    {
        return $this->hasOne(\common\models\Account::class, ['id' => 'created_id',]);
    }

    /** @var int 状态:沟通中 */
    const STATUS_1 = 1;
    /** @var int 状态:已成交 */
    const STATUS_2 = 2;
    /** @var int 状态:已作废 */
    const STATUS_3 = 3;
    /** @var string[] 状态 */
    const STATUS_MAP = [
        self::STATUS_1 => '沟通中',
        self::STATUS_2 => '已成交',
        self::STATUS_3 => '已作废',
    ];

    /** @var int 商机来源:搜索引擎 */
    const SOURCE_1 = 1;
    /** @var int 商机来源:广告 */
    const SOURCE_2 = 2;
    /** @var int 商机来源:转介绍 */
    const SOURCE_3 = 3;
    /** @var int 商机来源:线上咨询 */
    const SOURCE_4 = 4;
    /** @var int 商机来源:上门咨询 */
    const SOURCE_5 = 5;
    /** @var int 商机来源:其他 */
    const SOURCE_6 = 6;
    /** @var string[] 商机来源 */
    const SOURCE_MAP = [
        self::SOURCE_1 => '搜索引擎',
        self::SOURCE_2 => '广告',
        self::SOURCE_3 => '转介绍',
        self::SOURCE_4 => '线上咨询',
        self::SOURCE_5 => '上门咨询',
        self::SOURCE_6 => '其他',
    ];

    /** @var int 流失风险:未知 */
    const LOSS_RISK_1 = 1;
    /** @var int 流失风险:低 */
    const LOSS_RISK_2 = 2;
    /** @var int 流失风险:中 */
    const LOSS_RISK_3 = 3;
    /** @var int 流失风险:高 */
    const LOSS_RISK_4 = 4;
    /** @var string[] 流失风险 */
    const LOSS_RISK_MAP =[
        self::LOSS_RISK_1 => '未知',
        self::LOSS_RISK_2 => '低',
        self::LOSS_RISK_3 => '中',
        self::LOSS_RISK_4 => '高',
    ];

    /** @var int 是否分析:是 */
    const IS_ANALYZE_1 = 1;
    /** @var int 是否分析:否 */
    const IS_ANALYZE_2 = 2;
    /** @var string[] 是否分析 */
    const IS_ANALYZE_MAP = [
        self::IS_ANALYZE_1 => '是',
        self::IS_ANALYZE_2 => '否',
    ];

}
