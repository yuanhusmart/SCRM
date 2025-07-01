<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "suite_corp_fe_file".
 *
 * @property int $id
 * @property string $file_key 文件key或文件md5
 * @property string $file_value 文件值
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 */
class SuiteCorpFeFile extends \common\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_fe_file';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'updated_at'], 'integer'],
            [['file_key'], 'string', 'max' => 200],
            [['file_value'], 'string', 'max' => 500],
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

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'         => 'ID',
            'file_key'   => '文件KEY或文件MD5',
            'file_value' => '文件值',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
        ];
    }
}
