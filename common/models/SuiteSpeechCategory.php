<?php

namespace common\models;

use common\models\concerns\traits\Helper;
use common\models\concerns\traits\SoftDeletes;
use Yii;

/**
 * This is the model class for table "suite_speech_category".
 *
 * @property int $id
 * @property string $suite_id
 * @property string $corp_id
 * @property string $name
 * @property int $parent_id
 * @property int $created_at
 * @property int $updated_at
 * @property string $path
 * @property string $path_name
 * @property int $deleted_at
 */
class SuiteSpeechCategory extends \common\db\ActiveRecord
{
    use Helper;
    use SoftDeletes;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_speech_category';
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
            'name'       => 'Name',
            'parent_id'  => 'Parent ID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'path'       => 'Path',
            'path_name'  => 'Path Name',
            'deleted_at' => 'Deleted At',
        ];
    }

    public function getDepartments()
    {
        return $this->hasMany(SuiteSpeechCategoryDepartment::class, ['category_id' => 'id']);
    }
}
