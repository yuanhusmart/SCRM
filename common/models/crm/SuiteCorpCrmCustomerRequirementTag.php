<?php

namespace common\models\crm;

use common\models\concerns\traits\CorpNotSoft;
use common\models\concerns\traits\Helper;

/**
 * 客户联系人标签
 * create table suite_corp_crm_customer_requirement_tags
 * (
 * @property int $id                int unsigned auto_increment comment '主键ID' primary key,
 * @property string $suite_id   varchar(50)  default ''  not null comment '服务商ID=suite_corp_config.suite_id',
 * @property string $corp_id    varchar(50)  default ''  not null comment '企业ID=suite_corp_config.corp_id',
 * @property string $customer_no varchar(50)  default ''  not null comment '客户编号=suite_corp_crm_customer.customer_no',
 * @property string $contact_no varchar(50)  default ''  not null comment '客户联系人编号=suite_corp_crm_customer_contact.contact_no',
 * @property string $group_name varchar(255) default ''  not null comment '需求标签组名称',
 * @property string $tag_name varchar(255) default ''  not null comment '需求标签名称',
 * @property int $created_at int unsigned default '0' not null comment '创建时间',
 * @property int $updated_at int unsigned default '0' not null comment '更新时间',
 * unique key uk_main (suite_id, corp_id, customer_no, contact_no, group_name,tag_name)
 * )
 */
class SuiteCorpCrmCustomerRequirementTag extends \common\db\ActiveRecord
{
    use Helper;
    use CorpNotSoft;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_crm_customer_requirement_tags';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['suite_id', 'corp_id', 'customer_no','contact_no'], 'string', 'max' => 50],
            [['group_name','tag_name'], 'string', 'max' => 255],
            [['created_at', 'updated_at'], 'integer'],
            [
                ['suite_id', 'corp_id', 'customer_no', 'contact_no', 'group_name','tag_name'],
                'unique',
                'targetAttribute' => ['suite_id', 'corp_id', 'customer_no', 'contact_no', 'group_name','tag_name'],
            ],
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
            'group_name' => '标签的分组名称',
            'tag_name' => '标签名称',
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
}
