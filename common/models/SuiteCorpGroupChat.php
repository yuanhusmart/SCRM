<?php

namespace common\models;

/**
 * 服务商企业客户群表
 * This is the model class for table "suite_corp_group_chat".
 *
 * @property int $id
 * @property string $suite_id 服务商ID
 * @property string $corp_id 企业ID
 * @property string $chat_id 客户群ID
 * @property string $name 群名称
 * @property string $notes 备注
 * @property int $group_type 群组类型 1.客户群(外部群) 2.内部群
 * @property int $is_modify 名称是否可修改 1.可修改 2不可修改
 * @property int $create_time 创建时间
 * @property string $owner 群主ID
 * @property string $notice 群公告
 * @property string $member_version 当前群成员版本号。可以配合客户群变更事件减少主动调用本接口的次数
 * @property int $updated_at 更新时间
 * @property int $is_dismiss 解散 1.是 2.否
 * @property int $dismiss_time 解散时间
 * @property int $member_count 群员总数
 */
class SuiteCorpGroupChat extends \common\db\ActiveRecord
{

    // 名称是否可修改 1.可修改 2不可修改
    const IS_MODIFY_1 = 1;
    const IS_MODIFY_2 = 2;

    const IS_MODIFY = [
        self::IS_MODIFY_1 => '可修改',
        self::IS_MODIFY_2 => '不可修改',
    ];

    // 解散 1.是 2.否
    const IS_DISMISS_1 = 1;
    const IS_DISMISS_2 = 2;

    // 群组类型 1.客户群(外部群) 2.内部群
    const GROUP_TYPE_1 = 1;
    const GROUP_TYPE_2 = 2;

    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{suite_corp_group_chat}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['updated_at', 'create_time', 'is_dismiss', 'dismiss_time', 'member_count', 'group_type', 'is_modify'], 'integer'],
            [['updated_at', 'create_time', 'dismiss_time', 'member_count'], 'default', 'value' => 0],
            ['is_modify', 'default', 'value' => self::IS_MODIFY_2],
            ['group_type', 'default', 'value' => self::GROUP_TYPE_1],
            ['is_dismiss', 'default', 'value' => self::IS_DISMISS_2],
            [['suite_id', 'corp_id', 'chat_id', 'owner', 'member_version'], 'string', 'max' => 50],
            ['name', 'string', 'max' => 255],
            ['notes', 'string', 'max' => 100],
            ['notice', 'string', 'max' => 3000],
            [['suite_id', 'corp_id', 'chat_id', 'owner', 'member_version', 'notice', 'name','notes'], 'default', 'value' => ''],
        ];
    }

    public function beforeValidate()
    {
        $this->updated_at = time();
        return parent::beforeValidate();
    }

    /**
     * 关联服务商企业客户群表 获取内部成员 通过主键ID
     * @return \yii\db\ActiveQuery
     */
    public function getGroupChatMemberById()
    {
        return $this->hasMany(SuiteCorpGroupChatMember::class, ['group_chat_id' => 'id'])
                    ->select(['group_chat_id', 'userid', 'type', 'join_time', 'group_nickname', 'name','role', 'group_nickname_is_modify', 'chat_agree']);
    }

    /**
     * 关联服务商企业客户群表 获取内部成员 通过主键ID
     * @return \yii\db\ActiveQuery
     */
    public function getGroupChatExternalMemberById()
    {
        return $this->hasMany(SuiteCorpGroupChatMember::class, ['group_chat_id' => 'id'])->andWhere(['type' => SuiteCorpGroupChatMember::GROUP_CHAT_TYPE_2])->select(['group_chat_id', 'userid', 'type', 'join_time', 'group_nickname', 'name', 'role', 'group_nickname_is_modify']);
    }

}
