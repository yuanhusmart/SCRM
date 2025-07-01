<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "suite_corp_keyword".
 *
 * @property int $id 主键ID
 * @property string $suite_id 服务商ID
 * @property string $corp_id 企业ID
 * @property string $word 关键词，长度1~32个字符
 */
class SuiteCorpKeyword extends \yii\db\ActiveRecord
{

    const CHANGE_FIELDS = ['suite_id', 'corp_id', 'word'];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_keyword';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['suite_id', 'corp_id', 'word'], 'string', 'max' => 50],
            [['suite_id', 'corp_id', 'word'], 'unique', 'targetAttribute' => ['suite_id', 'corp_id', 'word']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'       => 'ID',
            'suite_id' => 'Suite ID',
            'corp_id'  => 'Corp ID',
            'word'     => 'Word',
        ];
    }
}
