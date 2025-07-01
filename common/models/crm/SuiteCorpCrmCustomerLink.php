<?php

namespace common\models\crm;

use common\models\concerns\traits\CorpNotSoft;

/**
 * 企业的CRM客户关系人表
 * create table suite_corp_crm_customer_link
 * (
 * @property int $id          int unsigned auto_increment comment '主键ID' primary key,
 * @property string $suite_id          varchar(50)      default ''  not null comment '服务商ID=suite_corp_config.suite_id',
 * @property string $corp_id     varchar(50)      default ''  not null comment '企业ID=suite_corp_config.corp_id',
 * @property string $customer_no varchar(50)      default ''  not null comment '客户编号=suite_corp_crm_customer.customer_no',
 * @property string $link_no     varchar(50)      default ''  not null comment '关系人编号',
 * @property int $relational  tinyint(3)       default 1   not null comment '关系:1维护人,2协作人',
 * @property int $account_id    int              default 0   not null comment '员工账号ID=suite_corp_accounts.id',
 * @property int $created_at  int(10) unsigned default '0' not null comment '创建时间',
 * @property int $updated_at  int(10) unsigned default '0' not null comment '更新时间',
 * unique key uk_no (link_no),
 * KEY idx_no (suite_id, corp_id, customer_no,relational)
 * KEY idx_account_id(account_id)
 * KEY idx_link_customer_account (customer_no, account_id)
 * )
 */
class SuiteCorpCrmCustomerLink extends \common\db\ActiveRecord
{
    use CorpNotSoft;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_crm_customer_link';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['suite_id', 'corp_id', 'customer_no', 'link_no'], 'string', 'max' => 50],
            [['created_at', 'updated_at','relational'], 'integer'],
            [['link_no'], 'unique', 'targetAttribute' => ['link_no']],
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
            'link_no' => '关系人编号',
            'relational' => '关系',
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
     * 一对一:关联员工账号
     * @return \yii\db\ActiveQuery
     */
    public function getAccount()
    {
        return $this->hasOne(\common\models\Account::class, ['id' => 'account_id']);
    }

    /** @var int 关系: 维护人 */
    const RELATIONAL_1 = 1;
    /** @var int 关系: 协作人 */
    const RELATIONAL_2 = 2;
    /** @var array 关系 */
    const RELATIONAL_MAP = [
        self::RELATIONAL_1 => '维护人',
        self::RELATIONAL_2 => '协作人',
    ];

}
