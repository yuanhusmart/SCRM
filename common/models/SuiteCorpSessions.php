<?php

namespace common\models;


/**
 * This is the model class for table "suite_corp_sessions".
 *
 * @property int $id 主键ID
 * @property string $suite_id 服务商ID
 * @property string $corp_id 企业ID
 * @property int $kind 类型: 1好友, 2群聊
 * @property string $session_id 会话ID,kind=1单聊=(发送人、接收人 字典升序md5),kind=2群聊=群组ID
 * @property string $chat_id 群组ID
 * @property string $name 昵称,群名
 * @property string $avatar 头像
 * @property string $remark 备注
 * @property int $last_at 最后消息时间
 * @property int $updated_at 更新时间
 * @property int $inside_or_outside 用于区分会话属于内部或者外部，1.内部；2.外部
 * @property int $msgid 最后一条消息ID
 */
class SuiteCorpSessions extends \common\db\ActiveRecord
{

    const CHANGE_FIELDS = ['suite_id', 'corp_id', 'kind', 'session_id', 'chat_id', 'name', 'avatar', 'remark', 'last_at', 'updated_at', 'inside_or_outside', 'msgid'];

    // 类型: 1好友, 2群聊
    const KIND_1 = 1;
    const KIND_2 = 2;

    // 内部或外部。1:内部;2:外部;
    const INSIDE_OR_OUTSIDE_1 = 1;
    const INSIDE_OR_OUTSIDE_2 = 2;

    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{suite_corp_sessions}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['kind', 'last_at', 'updated_at', 'inside_or_outside'], 'integer'],
            [['kind', 'last_at', 'updated_at', 'inside_or_outside'], 'default', 'value' => 0],
            [['suite_id', 'corp_id', 'session_id', 'chat_id'], 'string', 'max' => 50],
            [['msgid'], 'string', 'max' => 100],
            [['name'], 'string', 'max' => 255],
            [['avatar'], 'string', 'max' => 500],
            [['remark'], 'string', 'max' => 1000],
            [['suite_id', 'corp_id', 'session_id', 'chat_id', 'name', 'avatar', 'remark', 'msgid'], 'default', 'value' => ''],
            [['suite_id', 'corp_id', 'session_id'], 'unique', 'targetAttribute' => ['suite_id', 'corp_id', 'session_id']],
        ];
    }

    /**
     * 关联服务商企业客户群表 获取群信息 通过 chat_id
     * @return \yii\db\ActiveQuery
     */
    public function getGroupChatByChatId()
    {
        return $this->hasOne(SuiteCorpGroupChat::class, ['suite_id' => 'suite_id', 'corp_id' => 'corp_id', 'chat_id' => 'chat_id'])->select(['id', 'suite_id', 'corp_id', 'chat_id', 'name','notes', 'group_type', 'notice', 'member_count']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSessionsMemberById()
    {
        return $this->hasMany(SuiteCorpSessionsMember::class, ['suite_id' => 'suite_id', 'corp_id' => 'corp_id', 'session_id' => 'session_id']);
    }

}
