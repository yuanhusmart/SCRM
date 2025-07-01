<?php

namespace common\models;

use common\models\concerns\traits\Helper;

/**
 * sop设置版本
 * @property int $id
 * @property int $sop_no varchar(50)  default '' not null comment 'SOP配置编号',
 * @property int $version    int default 0 not null comment '版本',
 * @property json|null $content    json          null comment '内容',
 * @property int $created_at int default 0 not null comment '创建时间',
 * @property int $updated_at int default 0 not null comment '更新时间',
 */
class SuiteSopVersion extends \common\db\ActiveRecord
{
    use Helper;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_sop_version';
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'         => 'ID',
            'sop_no'     => 'Sop ID',
            'version'    => 'Version',
            'content'    => 'Content',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
