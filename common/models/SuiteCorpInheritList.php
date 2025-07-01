<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "suite_corp_inherit_list".
 *
 * @property int $id
 * @property int $inherit_id 关联服务商企业继承表主键ID
 * @property string $userid 交接人用户ID
 * @property string $heir 接收人用户ID
 * @property int $type 类型 1.客户 2.客户群
 * @property int $status 接替状态， 1-接替完毕 2-等待接替 3-客户拒绝 4-接替成员客户达到上限 9-失败
 * @property int $external_name 被继承名称关联type字段，type=1值为外部联系人的名称 type=2值为群组名称
 * @property string $external_id 被继承外部ID关联type字段，type=1值为外部联系人ID type=2值为群组ID
 * @property int $takeover_time 接替客户的时间，如果是等待接替状态，则为未来的自动接替时间
 * @property int $errmsg 失败原因
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 */
class SuiteCorpInheritList extends \yii\db\ActiveRecord
{
    // 接替状态， 1-接替完毕 2-等待接替 3-客户拒绝 4-接替成员客户达到上限 9-失败
    const STATUS_1 = 1;
    const STATUS_2 = 2;
    const STATUS_3 = 3;
    const STATUS_4 = 4;
    const STATUS_9 = 9;

    const STATUS_DESC = [
        self::STATUS_1 => '接替完毕',
        self::STATUS_2 => '等待接替',
        self::STATUS_3 => '客户拒绝',
        self::STATUS_4 => '接替成员客户达到上限',
        self::STATUS_9 => '失败',
    ];

    // 类型 1.客户 2.客户群
    const TYPE_1 = 1;
    const TYPE_2 = 2;

    const TYPE_DESC = [
        self::TYPE_1 => '客户',
        self::TYPE_2 => '客户群',
    ];

    const CHANGE_FIELDS = ['inherit_id', 'userid', 'heir', 'type', 'status', 'external_name', 'external_id', 'takeover_time', 'errmsg', 'created_at', 'updated_at'];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_inherit_list';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['inherit_id', 'type', 'status', 'takeover_time', 'created_at', 'updated_at'], 'integer'],
            [['inherit_id', 'type', 'status', 'takeover_time', 'created_at', 'updated_at'], 'default', 'value' => 0],
            [['userid', 'heir', 'external_id'], 'string', 'max' => 50],
            [['external_name'], 'string', 'max' => 255],
            [['errmsg'], 'string', 'max' => 500],
            [['userid', 'heir', 'external_name', 'external_id', 'errmsg'], 'default', 'value' => ''],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'            => 'ID',
            'inherit_id'    => 'Inherit ID',
            'userid'        => 'Userid',
            'heir'          => 'Heir',
            'type'          => 'Type',
            'status'        => 'Status',
            'external_name' => 'External Name',
            'external_id'   => 'External ID',
            'takeover_time' => 'Takeover Time',
            'errmsg'        => 'Err Msg',
            'created_at'    => 'Created At',
            'updated_at'    => 'Updated At',
        ];
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
