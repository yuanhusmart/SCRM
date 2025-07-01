<?php

namespace common\models;

use common\models\concerns\widgets\SuiteCorpConfig\InitRole;
use common\models\concerns\widgets\SuiteCorpConfig\RevisePackageRole;
use Yii;

/**
 * This is the model class for table "suite_corp_config".
 *
 * @property int $id
 * @property string $suite_id 服务商ID
 * @property string $corp_id 企业ID
 * @property string $corp_name 企业名称
 * @property string $suite_agent_id 授权方应用id
 * @property string $suite_agent_name 关联应用名称
 * @property string $creator 创建者
 * @property int $status 状态：1.启用 2.禁用
 * @property int $is_auto_auth 自动授权 1.开启 2.关闭
 * @property string $permanent_code 服务商企业永久授权码(代开发模板ID获取)
 * @property string $suite_permanent_code 服务商企业永久授权码(服务商ID获取)
 * @property int $package_id 企业购买的套餐ID,关联服务商套餐表主键ID
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property string $corp_scale 企业规模
 * @property string $corp_industry 企业所属行业
 * @property string $corp_sub_industry 企业所属子行业
 * @property int $subject_type 企业类型，1. 企业; 2. 政府以及事业单位; 3. 其他组织, 4.团队号
 * @property string $corp_type 授权方企业类型，认证号：verified, 注册号：unverified
 * @property int $verified_end_time 认证到期时间
 * @property int $tokens 已购token总数
 * @property int $use_tokens 已使用token总数
 */
class SuiteCorpConfig extends \common\db\ActiveRecord
{

    const CHANGE_FIELDS = ['corp_name', 'suite_agent_id', 'suite_agent_name', 'creator', 'status', 'is_auto_auth', 'permanent_code', 'suite_permanent_code', 'package_id', 'created_at', 'updated_at', 'corp_scale', 'corp_industry', 'corp_sub_industry', 'subject_type', 'corp_type', 'verified_end_time', 'tokens', 'use_tokens'];

    // 状态：1.启用 2.禁用
    const STATUS_1 = 1;
    const STATUS_2 = 2;

    const STATUS_DESC = [
        self::STATUS_1 => '启用',
        self::STATUS_2 => '禁用',
    ];

    // 自动授权 1.开启 2.关闭
    const IS_AUTO_AUTH_1 = 1;
    const IS_AUTO_AUTH_2 = 2;

    const IS_AUTO_AUTH_DESC = [
        self::IS_AUTO_AUTH_1 => '开启',
        self::IS_AUTO_AUTH_2 => '关闭',
    ];

    // 企业类型，1. 企业; 2. 政府以及事业单位; 3. 其他组织, 4.团队号
    const SUBJECT_TYPE_1 = 1;
    const SUBJECT_TYPE_2 = 2;
    const SUBJECT_TYPE_3 = 3;
    const SUBJECT_TYPE_4 = 4;

    const SUBJECT_TYPE_DESC = [
        self::SUBJECT_TYPE_1 => '企业',
        self::SUBJECT_TYPE_2 => '政府以及事业单位',
        self::SUBJECT_TYPE_3 => '其他组织',
        self::SUBJECT_TYPE_4 => '团队号',
    ];

    // 授权方企业类型，认证号：verified, 注册号：unverified
    const CORP_TYPE_VERIFIED   = 'verified';
    const CORP_TYPE_UNVERIFIED = 'unverified';

    const CORP_TYPE_DESC = [
        self::CORP_TYPE_VERIFIED   => '认证号',
        self::CORP_TYPE_UNVERIFIED => '注册号',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_config';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'is_auto_auth', 'created_at', 'updated_at', 'subject_type', 'verified_end_time', 'tokens', 'use_tokens', 'package_id'], 'integer'],
            [['status'], 'default', 'value' => self::STATUS_1],
            [['is_auto_auth'], 'default', 'value' => self::IS_AUTO_AUTH_2],
            [['tokens', 'use_tokens'], 'default', 'value' => 0],
            [['suite_id', 'corp_id', 'corp_name', 'suite_agent_id', 'suite_agent_name', 'creator', 'corp_scale'], 'string', 'max' => 50],
            [['permanent_code', 'suite_permanent_code'], 'string', 'max' => 200],
            [['corp_industry', 'corp_sub_industry'], 'string', 'max' => 100],
            [['corp_type'], 'string', 'max' => 30],
            [['suite_id', 'corp_id'], 'unique', 'targetAttribute' => ['suite_id', 'corp_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'                   => 'ID',
            'suite_id'             => 'Suite ID',
            'corp_id'              => 'Corp ID',
            'corp_name'            => 'Corp Name',
            'suite_agent_id'       => 'Suite Agent ID',
            'suite_agent_name'     => 'Suite Agent Name',
            'creator'              => 'Creator',
            'status'               => 'Status',
            'is_auto_auth'         => 'Is Auto Auth',
            'permanent_code'       => 'Permanent Code',
            'suite_permanent_code' => 'Suite Permanent Code',
            'package_id'           => 'Package ID',
            'created_at'           => 'Created At',
            'updated_at'           => 'Updated At',
            'corp_scale'           => 'Corp Scale',
            'corp_industry'        => 'Corp Industry',
            'corp_sub_industry'    => 'Corp Sub Industry',
            'subject_type'         => 'Subject Type',
            'corp_type'            => 'Corp Type',
            'verified_end_time'    => 'Verified End Time',
            'tokens'               => 'Tokens',
            'use_tokens'           => 'Use Tokens',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConfigAdminListById()
    {
        return $this->hasMany(SuiteCorpConfigAdminList::class, ['config_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLicenseActiveInfoCount()
    {
        return $this->hasMany(SuiteCorpLicenseActiveInfo::class, ['suite_id' => 'suite_id', 'corp_id' => 'corp_id'])
                    ->groupBy(['corp_id', 'type'])
                    ->select(['suite_id', 'corp_id', 'type', 'count(id) as count']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConfigChatAuthCount()
    {
        return $this->hasMany(SuiteCorpConfigChatAuth::class, ['config_id' => 'id'])
                    ->andWhere(['deleted_at' => 0])
                    ->groupBy(['config_id', 'edition'])
                    ->select(['config_id', 'edition', 'count(id) as count']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAccountCount()
    {
        return $this->hasOne(Account::class, ['suite_id' => 'suite_id', 'corp_id' => 'corp_id'])->andWhere(['deleted_at' => 0])->select(['suite_id', 'corp_id', 'count(id) as count']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPackageById()
    {
        return $this->hasOne(SuitePackage::class, ['id' => 'package_id'])->select(['id', 'name']);
    }

    /**
     * 初始化企业角色
     * @return void
     */
    public function initRole()
    {
        InitRole::make()->corp($this)->execute();
    }


    // 更新套餐修订角色
    public function revisePackageRole()
    {
        RevisePackageRole::make()->corp($this)->execute();
    }
}
