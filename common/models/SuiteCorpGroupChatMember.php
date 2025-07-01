<?php

namespace common\models;

/**
 * 服务商企业客户群表
 * This is the model class for table "suite_corp_group_chat_member".
 *
 * @property int $id
 * @property int $group_chat_id 关联服务商企业客户群表主键ID,内部使用
 * @property string $userid 群成员id
 * @property int $type 成员类型。1 - 企业成员 2 - 外部联系人
 * @property string $unionid 外部联系人在微信开放平台的唯一身份标识（微信unionid），通过此字段企业可将外部联系人与公众号/小程序用户关联起来。仅当群成员类型是微信用户（包括企业成员未添加好友），且企业绑定了微信开发者ID有此字段（查看绑定方法）。第三方不可获取，上游企业不可获取下游企业客户的unionid字段
 * @property int $join_time 入群时间
 * @property int $join_scene 入群方式。1 - 由群成员邀请入群（直接邀请入群）2 - 由群成员邀请入群（通过邀请链接入群）3 - 通过扫描群二维码入群
 * @property string $invitor_userid 邀请者的userid
 * @property string $group_nickname 在群里的昵称
 * @property string $name 名字。仅当 need_name = 1 时返回,如果是微信用户，则返回其在微信中设置的名字,如果是企业微信联系人，则返回其设置对外展示的别名或实名
 * @property int $role 群内角色 1.群成员 2.群管理员 3.群主
 * @property int $group_nickname_is_modify 名称是否可修改 1.可修改 2不可修改
 * @property int $updated_at 更新时间
 * @property int $chat_agree 同意存档 1.同意; 2.拒绝
 */
class SuiteCorpGroupChatMember extends \common\db\ActiveRecord
{

    // 名称是否可修改 1.可修改 2不可修改
    const GROUP_NICKNAME_IS_MODIFY_1 = 1;
    const GROUP_NICKNAME_IS_MODIFY_2 = 2;

    const GROUP_NICKNAME_IS_MODIFY = [
        self::GROUP_NICKNAME_IS_MODIFY_1 => '可修改',
        self::GROUP_NICKNAME_IS_MODIFY_2 => '不可修改',
    ];

    // 群内角色 1.群成员 2.群管理员 3.群主
    const GROUP_CHAT_MEMBER_ROLE_1 = 1;

    const GROUP_CHAT_MEMBER_ROLE_2 = 2;

    const GROUP_CHAT_MEMBER_ROLE_3 = 3;

    // 成员类型。1 - 企业成员 2 - 外部联系人 3 - 机器人
    const GROUP_CHAT_TYPE_1 = 1;
    const GROUP_CHAT_TYPE_2 = 2;
    const GROUP_CHAT_TYPE_3 = 3;

    // 入群方式。1 - 由群成员邀请入群（直接邀请入群）2 - 由群成员邀请入群（通过邀请链接入群）3 - 通过扫描群二维码入群
    const JOIN_SCENE_1 = 1;
    const JOIN_SCENE_2 = 2;
    const JOIN_SCENE_3 = 3;


    // 同意存档 1.同意; 2.拒绝
    const CHAT_AGREE_1 = 1;
    const CHAT_AGREE_2 = 2;

    const CHAT_AGREE_DESC = [
        self::CHAT_AGREE_1 => '同意',
        self::CHAT_AGREE_2 => '拒绝',
    ];

    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{suite_corp_group_chat_member}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['group_chat_id', 'type', 'join_time', 'join_scene', 'role', 'updated_at', 'group_nickname_is_modify','chat_agree'], 'integer'],
            [['group_chat_id', 'type', 'join_time', 'join_scene', 'role', 'updated_at'], 'default', 'value' => 0],
            [['chat_agree'], 'default', 'value' => self::CHAT_AGREE_1],
            ['group_nickname_is_modify', 'default', 'value' => self::GROUP_NICKNAME_IS_MODIFY_2],
            [['userid', 'unionid', 'invitor_userid'], 'string', 'max' => 50],
            [['group_nickname', 'name'], 'string', 'max' => 100],
            [['userid', 'unionid', 'invitor_userid', 'group_nickname', 'name'], 'default', 'value' => ''],
        ];
    }

    public function beforeValidate()
    {
        $this->updated_at = time();
        return parent::beforeValidate();
    }

    /**
     * 关联帐号表 获取群信息 通过 userid
     * @return \yii\db\ActiveQuery
     */
    public function getAccountByUserid()
    {
        return $this->hasOne(Account::class, ['userid' => 'userid'])->select(['userid', 'jnumber', 'username', 'nickname', 'avatar']);
    }

    /**
     * 关联服务商企业外部联系人表  通过 userid
     * @return \yii\db\ActiveQuery
     */
    public function getExternalContactByUserid()
    {
        return $this->hasOne(SuiteCorpExternalContact::class, ['external_userid' => 'userid'])->select(['external_userid', 'name', 'avatar', 'type', 'corp_name']);
    }

    /**
     * 关联服务商企业客户群表(本表)  通过 group_chat_id
     * @return \yii\db\ActiveQuery
     */
    public function getGroupChatMemberById()
    {
        return $this->hasMany(self::class, ['group_chat_id' => 'group_chat_id'])->select(['id', 'group_chat_id', 'group_nickname', 'name'])->limit(3);
    }
}
