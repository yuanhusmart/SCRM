<?php

namespace common\models;

use common\models\concerns\traits\Helper;
use Yii;

/**
 * This is the model class for table "suite_role_package".
 *
 * @property int $id
 * @property int $role_id
 * @property int $package_id
 * @property int $kind 角色类型: 1管理员 2其他成员
 */
class SuiteRolePackage extends \common\db\ActiveRecord
{
    use Helper;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_role_package';
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'         => 'ID',
            'role_id'    => 'Role ID',
            'package_id' => 'Package ID',
            'kind'       => 'Kind',
        ];
    }

    public function getRole()
    {
        return $this->hasOne(SuiteRole::class, ['id' => 'role_id']);
    }
}
