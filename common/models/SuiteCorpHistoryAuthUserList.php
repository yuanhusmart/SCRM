<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "suite_corp_history_auth_user_list".
 *
 * @property int $id 主键ID
 * @property string $suite_id 服务商ID
 * @property string $corp_id 企业ID
 * @property string $userid 微信userid
 * @property string $edition_list 生效的版本列表，1：内部会话；2：内外部会话；3：内外部会话以及语音通话
 * @property int $start_time 生效时间
 * @property int $end_time 失效时间
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 */
class SuiteCorpHistoryAuthUserList extends \yii\db\ActiveRecord
{
    const CHANGE_FIELDS = ['suite_id', 'corp_id', 'userid', 'edition_list', 'start_time', 'end_time'];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_history_auth_user_list';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['start_time', 'end_time', 'created_at', 'updated_at'], 'integer'],
            [['suite_id', 'corp_id'], 'string', 'max' => 50],
            [['userid', 'edition_list'], 'string', 'max' => 100],
            [['start_time', 'end_time', 'created_at', 'updated_at'], 'default', 'value' => 0],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'           => 'ID',
            'suite_id'     => '服务商ID',
            'corp_id'      => '企业ID',
            'userid'       => '微信userid',
            'edition_list' => '生效的版本列表',
            'start_time'   => '生效时间',
            'end_time'     => '失效时间',
            'created_at'   => '创建时间',
            'updated_at'   => '更新时间',
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
