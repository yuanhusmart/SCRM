<?php

namespace common\models;

use common\models\concerns\traits\Helper;
use Yii;

/**
 * This is the model class for table "suite_permission".
 *
 * @property int $id
 * @property int $type 权限类型: 1菜单权限  2组件权限
 * @property int $parent_id
 * @property string $name 权限名称
 * @property string $slug 权限标识
 * @property string $route 权限路由
 * @property int $level 权限级别: 1系统级 2企业级
 * @property int $is_hide 是否隐藏: 1是 2否
 * @property int $status 状态: 1启用  2禁用
 * @property int $created_at
 * @property int $updated_at
 * @property string $path
 * @property string $path_name
 * @property string $creator_id
 */
class SuitePermission extends \common\db\ActiveRecord
{
    use Helper;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_permission';
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'         => 'ID',
            'type'       => 'Type',
            'parent_id'  => 'Parent ID',
            'name'       => 'Name',
            'slug'       => 'Slug',
            'route'      => 'Route',
            'level'      => 'Level',
            'is_hide'    => 'Is Hide',
            'status'     => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'path'       => 'Path',
            'path_name'  => 'Path Name',
        ];
    }

    public function getCreator()
    {
        return $this->hasOne(Account::class, ['id' => 'creator_id']);
    }
}
