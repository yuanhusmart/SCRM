<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "suite_corp_chat_agree".
 *
 * @property int $id 主键ID
 * @property string $suite_id 服务商ID
 * @property string $corp_id 企业ID
 * @property string $session_id 会话ID,kind=1单聊=(发送人、接收人 字典升序md5),kind=2群聊=群组ID
 * @property string $sender_id 消息发送人ID
 * @property int $sender_type 消息发送者身份类型。1：员工；2：外部联系人; 3：机器人
 * @property int $msgtype 消息类型。枚举值定义见下方消息类型
 * @property string $msgid 消息ID
 * @property int $send_time 消息时间
 * @property int $updated_at 更新时间
 */
class SuiteCorpChatAgree extends \yii\db\ActiveRecord
{

    const CHANGE_FIELDS = ['suite_id', 'corp_id', 'session_id', 'sender_id', 'sender_type', 'msgtype', 'msgid', 'send_time'];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_chat_agree';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['sender_type', 'msgtype', 'send_time', 'updated_at'], 'integer'],
            [['suite_id', 'corp_id', 'session_id', 'sender_id'], 'string', 'max' => 50],
            [['msgid'], 'string', 'max' => 100],
            [['suite_id', 'corp_id', 'session_id', 'msgid'], 'unique', 'targetAttribute' => ['suite_id', 'corp_id', 'session_id', 'msgid']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'          => 'ID',
            'suite_id'    => 'Suite ID',
            'corp_id'     => 'Corp ID',
            'session_id'  => 'Session ID',
            'sender_id'   => 'Sender ID',
            'msgtype'     => 'Msgtype',
            'sender_type' => 'Sender Type',
            'msgid'       => 'Msgid',
            'send_time'   => 'Send Time',
            'updated_at'  => 'Updated At',
        ];
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        $this->updated_at = time();
        return parent::beforeValidate();
    }
}
