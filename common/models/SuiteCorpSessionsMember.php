<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "suite_corp_sessions_member".
 *
 * @property int $id 主键ID
 * @property string $suite_id 服务商ID
 * @property string $corp_id 企业ID
 * @property string $session_id 会话ID,kind=1单聊=(发送人、接收人 字典升序md5),kind=2群聊=群组ID
 * @property string $userid 用户id
 * @property int $user_type 消息身份类型 1员工 2外部联系人 3机器人
 */
class SuiteCorpSessionsMember extends \common\db\ActiveRecord
{

    //  消息身份类型。1：员工；2：外部联系人; 3：机器人;
    const USER_TYPE_1 = 1;
    const USER_TYPE_2 = 2;
    const USER_TYPE_3 = 3;

    const CHANGE_FIELDS = ['suite_id', 'corp_id', 'session_id', 'userid', 'user_type'];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_sessions_member';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_type'], 'integer'],
            [['suite_id', 'corp_id', 'session_id', 'userid'], 'string', 'max' => 50],
            [['suite_id', 'corp_id', 'session_id', 'userid'], 'unique', 'targetAttribute' => ['suite_id', 'corp_id', 'session_id', 'userid']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'         => 'ID',
            'suite_id'   => 'Suite ID',
            'corp_id'    => 'Corp ID',
            'session_id' => 'Session ID',
            'userid'     => 'Userid',
            'user_type'  => 'User Type',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getExternalContactByUserid()
    {
        return $this->hasOne(SuiteCorpExternalContact::class, ['suite_id' => 'suite_id', 'corp_id' => 'corp_id', 'external_userid' => 'userid'])
                    ->select(['id', 'suite_id', 'corp_id', 'external_userid', 'name', 'is_modify', 'avatar', 'type', 'corp_name']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAccountByUserid()
    {
        return $this->hasOne(Account::class, ['suite_id' => 'suite_id', 'corp_id' => 'corp_id', 'userid' => 'userid'])
                    ->select(['id', 'suite_id', 'corp_id', 'userid', 'avatar']);
    }

}
