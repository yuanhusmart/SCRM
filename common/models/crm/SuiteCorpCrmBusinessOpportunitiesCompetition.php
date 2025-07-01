<?php

namespace common\models\crm;

use common\models\concerns\traits\CorpNotSoft;
use common\models\concerns\traits\Helper;

/**
 * 商机竞品记录
 * create table suite_corp_crm_business_opportunities_competition
 * (
 * @property int $id                int unsigned auto_increment comment '主键ID' primary key,
 * @property string $suite_id   varchar(50)  default ''  not null comment '服务商ID=suite_corp_config.suite_id',
 * @property string $corp_id                   varchar(50)  default ''  not null comment '企业ID=suite_corp_config.corp_id',
 * @property string $business_opportunities_no varchar(50)  default ''  not null comment '商机编号=suite_corp_crm_business_opportunities.business_opportunities_no',
 * @property string $name varchar(255) default ''  not null comment '竞品名称',
 * @property string $advantage                 varchar(255) default ''  not null comment '竞品优势',
 * @property int $created_at int unsigned default '0' not null comment '创建时间',
 * @property int $updated_at int unsigned default '0' not null comment '更新时间',
 * unique key uk_main (suite_id, corp_id, business_opportunities_no, name, advantage)
 * )
 */
class SuiteCorpCrmBusinessOpportunitiesCompetition extends \common\db\ActiveRecord
{
    use Helper;
    use CorpNotSoft;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_crm_business_opportunities_competition';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['suite_id', 'corp_id', 'business_opportunities_no'], 'string', 'max' => 50],
            [['name','advantage'], 'string', 'max' => 255],
            [['created_at', 'updated_at'], 'integer'],
            [
                ['suite_id', 'corp_id', 'business_opportunities_no','name','advantage'],
                'unique',
                'targetAttribute' => ['suite_id', 'corp_id', 'business_opportunities_no','name','advantage'],
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
            'business_opportunities_no' => '商机编号',
            'name' => '竞品名称',
            'advantage' => '竞品优势',
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
