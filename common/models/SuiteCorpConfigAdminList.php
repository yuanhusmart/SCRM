<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "suite_corp_config_admin_list".
 *
 * @property int $id 主键ID
 * @property int $config_id 关联服务商企业配置表主键ID
 * @property string $userid 用户id
 * @property int $auth_type 该管理员对应用的权限：0=发消息权限，1=管理权限
 */
class SuiteCorpConfigAdminList extends \yii\db\ActiveRecord
{

    const CHANGE_FIELDS = ['config_id', 'userid', 'auth_type'];

    // 该管理员对应用的权限：0=发消息权限，1=管理权限
    const AUTH_TYPE_0 = 0;
    const AUTH_TYPE_1 = 1;

    const AUTH_TYPE_DESC = [
        self::AUTH_TYPE_0 => '发消息权限',
        self::AUTH_TYPE_1 => '管理权限',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_config_admin_list';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['config_id', 'auth_type'], 'integer'],
            [['userid'], 'string', 'max' => 50],
            [['config_id', 'userid'], 'unique', 'targetAttribute' => ['config_id', 'userid']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'        => 'ID',
            'config_id' => 'Config ID',
            'userid'    => 'Userid',
            'auth_type' => 'Auth Type',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAccountByUserId()
    {
        return $this->hasOne(Account::class, ['userid' => 'userid'])->select('userid,nickname,jnumber');
    }

}
