<?php

namespace common\models;

use common\models\concerns\traits\Helper;
use Yii;

/**
 * This is the model class for table "suite_speech_lib".
 *
 * @property int $id
 * @property string $suite_id
 * @property string $corp_id
 * @property int $category_id
 * @property string $industry_no 行业编号
 * @property string $name
 * @property int $type 类型: 1文本, 2附件
 * @property string $content 内容
 * @property int $status 状态: 1启用 2禁用
 * @property int $operator_id 操作人ID
 * @property int $created_at
 * @property int $updated_at
 * @property SuiteSpeechCategory $category
 * @property SuiteFile $file
 */
class SuiteSpeech extends \common\db\ActiveRecord
{
    use Helper;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_speech';
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
            'category_id' => 'Category ID',
            'industry_no' => 'Industry No',
            'name'        => 'Name',
            'type'        => 'Type',
            'content'     => 'Content',
            'status'      => 'Status',
            'operator_id' => 'Operator ID',
            'created_at'  => 'Created At',
            'updated_at'  => 'Updated At',
        ];
    }

    public function getCategory()
    {
        return $this->hasOne(SuiteSpeechCategory::class, ['id' => 'category_id']);
    }

    public function getFile()
    {
        return $this->hasOne(SuiteFile::class, ['belong_id' => 'id'])->where(['belong_type' => self::class]);
    }

    public function getOperator()
    {
        return $this->hasOne(Account::class, ['id' => 'operator_id']);
    }
}
