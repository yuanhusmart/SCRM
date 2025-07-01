<?php

namespace common\models;

use common\models\concerns\traits\Corp;
use common\models\concerns\traits\Helper;
use common\models\concerns\traits\SoftDeletes;

/**
 * This is the model class for table "suite_role".
 *
 * @property int $id 主键ID
 * @property string $suite_id
 * @property string $corp_id 企业ID
 * @property string $name 角色名称
 * @property string $description 描述
 * @property int $type 角色类型：1.基础 2.自定义
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property int $deleted_at 删除时间
 * @property int $is_default 是否默认角色：1.是 2.否
 * @property int $kind 类型: 1企业角色, 2套餐角色
 * @property int $is_admin
 */
class SuiteRole extends \common\db\ActiveRecord
{
    use Helper;
    use SoftDeletes;
    use Corp;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_role';
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'          => 'ID',
            'name'        => 'Name',
            'description' => 'Description',
            'type'        => 'Type',
            'created_at'  => 'Created At',
            'updated_at'  => 'Updated At',
        ];
    }

}
