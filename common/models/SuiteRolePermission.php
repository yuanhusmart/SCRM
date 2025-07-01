<?php

namespace common\models;

use common\models\concerns\traits\Helper;
use Yii;

/**
 * This is the model class for table "suite_role_permission".
 *
 * @property int $id
 * @property int $role_id
 * @property int $permission_id
 */
class SuiteRolePermission extends \common\db\ActiveRecord
{
    use Helper;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_role_permission';
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'role_id' => 'Role ID',
            'permission_id' => 'Permission ID',
        ];
    }
}
