<?php

namespace common\models;

use common\models\concerns\traits\CorpNotSoft;

/**
 * 服务商企业外部联系人表
 * This is the model class for table "suite_corp_external_contact".
 *
 * @property int $id
 * @property string $suite_id 服务商ID
 * @property string $corp_id 企业ID
 * @property string $external_userid 外部联系人的userid，注意不是企业成员的账号
 * @property string $name 外部联系人的名称
 * @property string $avatar 外部联系人头像，代开发自建应用需要管理员授权才可以获取，第三方不可获取，上游企业不可获取下游企业客户该字段
 * @property int $type 外部联系人的类型，1表示该外部联系人是微信用户，2表示该外部联系人是企业微信用户
 * @property int $is_modify 名称是否可修改 1.可修改 2不可修改
 * @property int $gender 外部联系人性别 0-未知 1-男性 2-女性。第三方不可获取，上游企业不可获取下游企业客户该字段，返回值为0，表示未定义
 * @property string $unionid 外部联系人头像，代开发自建应用需要管理员授权才可以获取，第三方不可获取，上游企业不可获取下游企业客户该字段
 * @property string $position 外部联系人的职位，如果外部企业或用户选择隐藏职位，则不返回，仅当联系人类型是企业微信用户时有此字段
 * @property string $corp_name 外部联系人所在企业的简称，仅当联系人类型是企业微信用户时有此字段
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 */
class SuiteCorpExternalContact extends \common\db\ActiveRecord
{
    use CorpNotSoft;

    // 名称是否可修改 1.可修改 2不可修改
    const IS_MODIFY_1 = 1;
    const IS_MODIFY_2 = 2;

    const IS_MODIFY = [
        self::IS_MODIFY_1 => '可修改',
        self::IS_MODIFY_2 => '不可修改',
    ];

    // 外部联系人的类型，1表示该外部联系人是微信用户，2表示该外部联系人是企业微信用户
    const ENUM_TYPE = [
        1 => '表示该外部联系人是微信用户',
        2 => '表示该外部联系人是企业微信用户',
    ];

    // 外部联系人性别 0-未知 1-男性 2-女性。第三方不可获取，上游企业不可获取下游企业客户该字段，返回值为0，表示未定义
    const ENUM_GENDER = [
        0 => '未知',
        1 => '男',
        2 => '女',
    ];

    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{suite_corp_external_contact}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['updated_at', 'created_at', 'type', 'gender', 'is_modify'], 'integer'],
            [['updated_at', 'created_at', 'type', 'gender'], 'default', 'value' => 0],
            ['is_modify', 'default', 'value' => self::IS_MODIFY_2],
            [['suite_id', 'corp_id', 'external_userid', 'unionid', 'position', 'corp_name'], 'string', 'max' => 50],
            ['name', 'string', 'max' => 100],
            ['avatar', 'string', 'max' => 255],
            [['suite_id', 'corp_id', 'external_userid', 'unionid', 'position', 'corp_name', 'name', 'avatar'], 'default', 'value' => ''],
        ];
    }

    /**
     * 关联服务商企业外部联系人关注用户表
     * @return \yii\db\ActiveQuery
     */
    public function getExternalContactFollowUsers()
    {
        return $this->hasMany(SuiteCorpExternalContactFollowUser::class, ['external_contact_id' => 'id'])->select(['id', 'external_contact_id', 'userid', 'remark']);
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
