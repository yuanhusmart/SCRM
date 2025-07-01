<?php

namespace common\models;

use common\models\concerns\traits\Corp;

/**
 * This is the model class for table "suite_corp_accounts".
 *
 * @property int $id 主键ID
 * @property string $suite_id 服务商ID
 * @property string $corp_id 企业ID
 * @property string $userid 微信userid
 * @property string $nickname 姓名
 * @property string $avatar 头像
 * @property int $status 状态: 1=已激活，2=已禁用，4=未激活，5=退出企业。
 * @property int $friends_number 好友数
 * @property int $groups_number 微信群数
 * @property int $login_times 历史设备登录次数
 * @property int $logout_times 历史设备登出次数
 * @property string $contact_way_config_id 新增联系方式的配置id
 * @property string $qr_code 联系我二维码链接，仅在scene为2时返回
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property int $deleted_at 删除时间
 * @property int $system_auth 系统授权: 1开启 2关闭
 */
class Account extends \common\db\ActiveRecord
{
    use Corp;

    const ACCOUNT_STATUS_1 = 1;
    const ACCOUNT_STATUS_2 = 2;
    const ACCOUNT_STATUS_4 = 4;
    const ACCOUNT_STATUS_5 = 5;

    // 状态: 1=已激活，2=已禁用，4=未激活，5=退出企业
    const ACCOUNT_STATUS = [
        self::ACCOUNT_STATUS_1 => '已激活',
        self::ACCOUNT_STATUS_2 => '已禁用',
        self::ACCOUNT_STATUS_4 => '未激活',
        self::ACCOUNT_STATUS_5 => '退出企业',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{suite_corp_accounts}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status', 'friends_number', 'groups_number', 'login_times', 'logout_times', 'created_at', 'updated_at', 'deleted_at'], 'integer'],
            [['suite_id', 'corp_id', 'contact_way_config_id'], 'string', 'max' => 50],
            [['userid', 'nickname'], 'string', 'max' => 100],
            [['avatar'], 'string', 'max' => 500],
            [['qr_code'], 'string', 'max' => 255],
            [['suite_id', 'corp_id', 'nickname', 'userid', 'avatar', 'qr_code', 'contact_way_config_id'], 'default', 'value' => '']
        ];
    }

    /**
     * 关联 企业员工帐号所在部门表
     * @return \yii\db\ActiveQuery
     */
    public function getAccountsDepartmentByAccount()
    {
        return $this->hasMany(SuiteCorpAccountsDepartment::class, ['suite_id' => 'suite_id', 'corp_id' => 'corp_id', 'userid' => 'userid']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLicenseActiveInfoByAccount()
    {
        return $this->hasMany(SuiteCorpLicenseActiveInfo::class, ['suite_id' => 'suite_id', 'corp_id' => 'corp_id', 'userid' => 'userid'])->andWhere(['status' => SuiteCorpLicenseActiveInfo::STATUS_2])->select('id,suite_id,corp_id,userid,type');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getChatAuthByAccount()
    {
        return $this->hasMany(SuiteCorpConfigChatAuth::class, ['suite_id' => 'suite_id', 'corp_id' => 'corp_id', 'userid' => 'userid'])
                    ->andWhere(['deleted_at' => 0])->select('id,suite_id,corp_id,userid,edition');
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'                    => 'ID',
            'suite_id'              => 'Suite ID',
            'corp_id'               => 'Corp ID',
            'userid'                => 'Userid',
            'nickname'              => 'Nickname',
            'avatar'                => 'Avatar',
            'status'                => 'Status',
            'friends_number'        => 'Friends Number',
            'groups_number'         => 'Groups Number',
            'login_times'           => 'Login Times',
            'logout_times'          => 'Logout Times',
            'contact_way_config_id' => 'Contact Way Config ID',
            'qr_code'               => 'Qr Code',
            'created_at'            => 'Created At',
            'updated_at'            => 'Updated At',
            'deleted_at'            => 'Deleted At',
        ];
    }

    public function getRoles()
    {
        return $this->hasMany(SuiteRole::class, ['id' => 'role_id'])
                    ->viaTable('suite_role_account', ['account_id' => 'id']);
    }
}
