<?php

namespace common\models\crm;

use common\models\concerns\traits\Corp;

/**
 * 企业的CRM客户表
 * create table suite_corp_crm_customer
 * (
 * @property int $id                   int unsigned auto_increment comment '主键ID' primary key,
 * @property string $suite_id          varchar(50)      default ''  not null comment '服务商ID=suite_corp_config.suite_id',
 * @property string $corp_id           varchar(50)      default ''  not null comment '企业ID=suite_corp_config.corp_id',
 * @property string $customer_no       varchar(50)      default ''  not null comment '客户编号',
 * @property string $customer_name     varchar(50)      default ''  not null comment '客户名称',
 * @property string $customer_address  varchar(255)     default ''  not null comment '客户联系地址',
 * @property string $network_address   varchar(255)     default ''  not null comment '客户网址',
 * @property int $source               tinyint(3)       unsigned default 1 not null comment '客户来源:1搜索引擎,2广告,3转介绍,4线上咨询,5线下地推,6其他',
 * @property string $remark            varchar(255)     default ''  not null comment '备注',
 * @property int $last_follow_at       int(10) unsigned default '0' not null comment '最近跟进时间=last suite_corp_crm_customer_follow.created_at',
 * @property int $created_at           int(10) unsigned default '0' not null comment '创建时间',
 * @property int $updated_at           int(10) unsigned default '0' not null comment '更新时间',
 * @property int $deleted_at           int(10) unsigned default 0  not null comment '删除时间',
 * UNIQUE KEY uk_no (customer_no),
 * KEY idx_no (suite_id, corp_id, customer_no),
 * KEY idx_last_follow_at (last_follow_at)
 * )
 */
class SuiteCorpCrmCustomer extends \common\db\ActiveRecord
{
    use Corp;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_crm_customer';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['source', 'created_at', 'updated_at', 'deleted_at','last_follow_at'], 'integer'],
            [['suite_id', 'corp_id', 'customer_no', 'customer_name'], 'string', 'max' => 50],
            [['customer_address', 'network_address', 'remark'], 'string', 'max' => 255],
            [['customer_no'], 'unique', 'targetAttribute' => ['customer_no']],
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
            'customer_name' => '客户名称',
            'customer_address' => '客户联系地址',
            'network_address' => '客户网址',
            'remark' => '备注',
            'source' => '客户来源',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'last_follow_at' => '最近跟进时间',
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
     * 一对多:关联联系人
     * @return \yii\db\ActiveQuery
     */
    public function getContacts()
    {
        return $this->hasMany(SuiteCorpCrmCustomerContact::class, ['customer_no' => 'customer_no'])->andWhere([SuiteCorpCrmCustomerContact::asField('deleted_at') => 0,]);
    }

    /**
     * 一对多:关联关系人
     * @return \yii\db\ActiveQuery
     */
    public function getLinks()
    {
        return $this->hasMany(SuiteCorpCrmCustomerLink::class, ['customer_no' => 'customer_no']);
    }

    /**
     * 一对多:关联商机
     * @return \yii\db\ActiveQuery
     */
    public function getBusinessOpportunities()
    {
        return $this->hasMany(SuiteCorpCrmBusinessOpportunities::class, ['customer_no' => 'customer_no']);
    }

    /** @var int 客户来源:搜索引擎 */
    const SOURCE_1 = 1;
    /** @var int 客户来源:广告 */
    const SOURCE_2 = 2;
    /** @var int 客户来源:转介绍 */
    const SOURCE_3 = 3;
    /** @var int 客户来源:线上咨询 */
    const SOURCE_4 = 4;
    /** @var int 客户来源:线下地推 */
    const SOURCE_5 = 5;
    /** @var int 客户来源:其他 */
    const SOURCE_6 = 6;
    /** @var string[] 客户来源 */
    const SOURCE_MAP = [
        self::SOURCE_1 => '搜索引擎',
        self::SOURCE_2 => '广告',
        self::SOURCE_3 => '转介绍',
        self::SOURCE_4 => '线上咨询',
        self::SOURCE_5 => '线下地推',
        self::SOURCE_6 => '其他',
    ];

}
