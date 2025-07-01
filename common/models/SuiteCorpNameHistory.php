<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "suite_corp_group_name_history".
 *
 * @property int $id
 * @property string $suite_id 服务商ID
 * @property string $corp_id 企业ID
 * @property int $type 类型：1.群组 2.群组成员id 3.外部联系人
 * @property string $origin_userid 源头用户ID
 * @property string $origin_name 源头姓名
 * @property string $chat_id 客户群ID
 * @property string $userid 用户ID
 * @property string $name 名称
 * @property int $created_at 创建时间
 * @property string $creator 创建者
 * @property int $create_number 创建者工号
 * @property string $create_userid 创建者用户ID
 */
class SuiteCorpNameHistory extends \yii\db\ActiveRecord
{

    // 类型：1.群组 2.群组成员id 3.外部联系人
    const GROUP_NAME_HISTORY_TYPE_1 = 1;
    const GROUP_NAME_HISTORY_TYPE_2 = 2;
    const GROUP_NAME_HISTORY_TYPE_3 = 3;

    const GROUP_NAME_HISTORY_TYPE = [
        self::GROUP_NAME_HISTORY_TYPE_1 => '群组',
        self::GROUP_NAME_HISTORY_TYPE_2 => '群组成员',
        self::GROUP_NAME_HISTORY_TYPE_3 => '外部联系人',
    ];

    const CHANGE_FIELDS = ['suite_id', 'corp_id', 'type', 'origin_userid', 'origin_name', 'chat_id', 'userid', 'name', 'creator', 'create_number', 'create_userid'];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_name_history';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'created_at', 'create_number'], 'integer'],
            [['type', 'created_at', 'create_number'], 'default', 'value' => 0],
            [['suite_id', 'corp_id', 'origin_userid', 'chat_id', 'userid', 'creator', 'create_userid'], 'string', 'max' => 50],
            [['origin_name', 'name'], 'string', 'max' => 100],
            [['suite_id', 'corp_id', 'origin_userid', 'chat_id', 'userid', 'creator', 'create_userid', 'origin_name', 'name'], 'default', 'value' => ''],
        ];
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        $this->created_at = time();
        return parent::beforeValidate();
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'            => 'ID',
            'suite_id'      => 'Suite ID',
            'corp_id'       => 'Corp ID',
            'type'          => 'Type',
            'origin_userid' => 'Origin Userid',
            'origin_name'   => 'Origin Name',
            'chat_id'       => 'Chat ID',
            'userid'        => 'Userid',
            'name'          => 'Name',
            'created_at'    => 'Created At',
            'creator'       => 'Creator',
            'create_number' => 'Create Number',
            'create_userid' => 'Create Userid',
        ];
    }
}
