<?php

namespace common\models;

use common\models\concerns\traits\Helper;
use Yii;

/**
 * This is the model class for table "suite_speech_category_department".
 *
 * @property int $id
 * @property int $category_id
 * @property int $department_id
 * @property string $path
 */
class SuiteSpeechCategoryDepartment extends \common\db\ActiveRecord
{
    use Helper;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_speech_category_department';
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'            => 'ID',
            'category_id'   => 'Category ID',
            'department_id' => 'Department ID',
            'path'          => 'Path',
        ];
    }
}
