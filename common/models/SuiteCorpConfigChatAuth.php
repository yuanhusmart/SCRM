<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "suite_corp_config_chat_auth".
 *
 * @property int $id 主键ID
 * @property int $config_id 关联服务商企业配置表主键ID
 * @property string $suite_id 服务商ID
 * @property string $corp_id 企业ID
 * @property string $userid 用户id
 * @property int $edition 生效的版本列表，1：内部会话；2：内外部会话；3：内外部会话以及语音通话
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property int $deleted_at 删除时间
 */
class SuiteCorpConfigChatAuth extends \yii\db\ActiveRecord
{

    const CHANGE_FIELDS = ['config_id', 'suite_id', 'corp_id', 'userid', 'edition', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_config_chat_auth';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['config_id', 'edition', 'created_at', 'updated_at', 'deleted_at'], 'integer'],
            [['suite_id', 'corp_id', 'userid'], 'string', 'max' => 50],
            [['config_id', 'userid', 'edition'], 'unique', 'targetAttribute' => ['config_id', 'userid', 'edition']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'         => 'ID',
            'config_id'  => 'Config ID',
            'suite_id'   => 'Suite ID',
            'corp_id'    => 'Corp ID',
            'userid'     => 'Userid',
            'edition'    => 'Edition',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'deleted_at' => 'Deleted At',
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
