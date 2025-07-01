<?php

namespace common\models;

use common\models\concerns\traits\Helper;

/**
 * sop阶段配置
 * @property int $id
 * @property int $sop_id      int          default 0  not null comment 'SOP模板ID=suite_sop.id',
 * @property string $name        varchar(50)  default '' not null comment '名称',
 * @property string $description varchar(500) default '' not null comment '阶段描述',
 * @property string $indicator   varchar(255) default '' not null comment '阶段指标',
 * @property json|null $speech      json                    null comment '标准话术',
 * @property json|null $todo_item   json                    null comment '待办事项',
 * @property int $sort        int          default 1  not null comment '排序字段',
 * @property int $type        tinyint      default 2  not null comment '阶段类型: 1必要阶段 2非必要阶段',
 */
class SuiteSopItem extends \common\db\ActiveRecord
{
    use Helper;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_sop_items';
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'          => 'ID',
            'sop_id'      => 'SOP模板ID',
            'name'        => '名称',
            'description' => '阶段描述',
            'indicator'  => '阶段指标',
            'speech'     => '标准话术',
            'todo_item'  => '待办事项',
            'sort'       => '排序字段',
            'type'       => '阶段类型: 1必要阶段 2非必要阶段',
        ];
    }
}
