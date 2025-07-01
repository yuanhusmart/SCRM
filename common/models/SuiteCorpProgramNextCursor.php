<?php

namespace common\models;

/**
 * This is the model class for table "suite_corp_program_next_cursor".
 *
 * @property int $id
 * @property string $suite_id 服务商ID
 * @property string $corp_id 企业ID
 * @property string $next_cursor 上一次调用时返回的next_cursor，第一次拉取可以不填。若不填，从3天内最早的消息开始返回。不多于64字节
 * @property int $updated_at 创建时间
 */
class SuiteCorpProgramNextCursor extends \common\db\ActiveRecord
{

    public static function tableName()
    {
        return '{{suite_corp_program_next_cursor}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['updated_at'], 'integer'],
            [['suite_id','corp_id'], 'string', 'max' => 32],
            ['next_cursor', 'string', 'max' => 100],
        ];
    }

    public function beforeValidate()
    {
        $this->updated_at = time();
        return parent::beforeValidate();
    }

}
