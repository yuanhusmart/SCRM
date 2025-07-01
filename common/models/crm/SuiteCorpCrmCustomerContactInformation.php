<?php

namespace common\models\crm;

/**
 * 企业的CRM客户联系人联系方式表(一个联系人多种联系方式)
 * create table suite_corp_crm_customer_contact_information
 * (
 * @property int $id                       int unsigned auto_increment comment '主键ID' primary key,
 * @property string $contact_no            varchar(50)      default ''  not null comment '联系人编号=suite_corp_crm_customer_contact.contact_no',
 * @property int $contact_information_type tinyint(3)       default 1   not null comment '联系方式类型:1手机,2个人微信,3邮箱,4企业微信',
 * @property string $contact_number        varchar(255)      default ''  not null comment '联系方式号码(泛指)',
 * @property int $created_at               int(10) unsigned default '0' not null comment '创建时间',
 * @property int $updated_at               int(10) unsigned default '0' not null comment '更新时间',
 * unique key uk_main (contact_no, contact_information_type, contact_number)
 * )
 */
class SuiteCorpCrmCustomerContactInformation extends \common\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_crm_customer_contact_information';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['contact_no'], 'string', 'max' => 50],
            [['contact_number'], 'string', 'max' => 255],
            [['created_at', 'updated_at','contact_information_type'], 'integer'],
            [['contact_no','contact_information_type','contact_number'], 'unique', 'targetAttribute' => ['contact_no','contact_information_type','contact_number']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'contact_no' => '联系人编号',
            'contact_information_type' => '联系方式类型',
            'contact_number' => '联系方式号码',
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

    public function getQwContact()
    {
        return $this->hasOne(SuiteCorpCrmCustomerContact::class, ['contact_no' => 'contact_no',])->andWhere(['deleted_at' => 0]);
    }

    /** @var int 联系方式类型:手机 */
    const CONTACT_INFORMATION_TYPE_1 = 1;
    /** @var int 联系方式类型:微信 */
    const CONTACT_INFORMATION_TYPE_2 = 2;
    /** @var int 联系方式类型:邮箱 */
    const CONTACT_INFORMATION_TYPE_3 = 3;
    /** @var int 联系方式类型:企业微信 */
    const CONTACT_INFORMATION_TYPE_4 = 4;
    /** @var string[] 联系方式类型 */
    const CONTACT_INFORMATION_TYPE_MAP = [
        self::CONTACT_INFORMATION_TYPE_1 => '手机',
        self::CONTACT_INFORMATION_TYPE_2 => '个人微信',
        self::CONTACT_INFORMATION_TYPE_3 => '邮箱',
        self::CONTACT_INFORMATION_TYPE_4 => '企业微信',
    ];

}
