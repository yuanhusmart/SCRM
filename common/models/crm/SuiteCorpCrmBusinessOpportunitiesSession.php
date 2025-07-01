<?php

namespace common\models\crm;

use common\models\concerns\traits\CorpNotSoft;

/**
 * 商机会话关联表
 * create table suite_corp_crm_business_opportunities_session
 * (
 * @property int $id                                    int unsigned auto_increment comment '主键ID' primary key,
 * @property string $suite_id                           varchar(50)  default ''  not null comment '服务商ID=suite_corp_config.suite_id',
 * @property string $corp_id                            varchar(50)  default ''  not null comment '企业ID=suite_corp_config.corp_id',
 * @property string $session_id                         varchar(50)  default ''  not null comment '会话ID,kind=1单聊=(发送人、接收人 字典升序md5),kind=2群聊=群组ID',
 * @property string $business_opportunities_no          varchar(50)  default ''  not null comment '商机编号=suite_corp_crm_business_opportunities.business_opportunities_no',
 * @property string $contact_no                         varchar(50)  default ''  not null comment '客户联系人编号=suite_corp_crm_customer_contact.contact_no',
 * @property string $business_opportunities_link_no     varchar(50)  default ''  not null comment '商机关系人编号=suite_corp_crm_business_opportunities_link.business_opportunities_link_no',
 * UNIQUE KEY uk_main (suite_id, corp_id, session_id, business_opportunities_no)
 * )
 */
class SuiteCorpCrmBusinessOpportunitiesSession extends \common\db\ActiveRecord
{
    use CorpNotSoft;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_crm_business_opportunities_session';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['suite_id', 'corp_id', 'session_id', 'business_opportunities_no', 'contact_no', 'business_opportunities_link_no'], 'string', 'max' => 50],
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
            'session_id' => '会话ID',
            'business_opportunities_no' => '商机编号',
            'contact_no' => '客户联系人编号',
            'business_opportunities_link_no' => '商机关系人编号',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
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
     * 一对一:关联商机
     * @return \yii\db\ActiveQuery
     */
    public function getBusinessOpportunities()
    {
        return $this->hasOne(SuiteCorpCrmBusinessOpportunities::class, ['business_opportunities_no' => 'business_opportunities_no']);
    }

}
