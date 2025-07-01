<?php

namespace common\models;

use common\models\concerns\traits\CorpNotSoft;
use common\models\concerns\traits\Helper;

/**
 * SOP配置
 * @property int $id
 * @property string $suite_id         varchar(50) default '' not null comment '服务商ID=suite_corp_config.suite_id',
 * @property string $corp_id          varchar(50) default '' not null comment '企业ID=suite_corp_config.corp_id',
 * @property string $industry_no varchar(50)  default '' not null comment '行业编号',
 * @property string $sop_no varchar(50)  default '' not null comment 'SOP配置编号',
 * @property int $created_at int          default 0  not null comment '创建时间',
 * @property int $updated_at int          default 0  not null comment '更新时间',
 * @property int $version    int          default 1  not null comment '版本',
 */
class SuiteSop extends \common\db\ActiveRecord
{
    use Helper;
    use CorpNotSoft;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_sop';
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'suite_id' => '服务商ID',
            'corp_id' => '企业ID',
            'industry_no' => '行业编号',
            'sop_no' => 'SOP配置编号',
            'created_at' => '创建时间',
            'updated_at' => '更新时间',
            'version' => '版本',
        ];
    }

    public function getItems()
    {
        return $this->hasMany(SuiteSopItem::class, ['sop_no' => 'sop_no']);
    }

    public function createVersion()
    {
        // 加载关联关系
        $this->items;
        $version = new SuiteSopVersion();
        $version->sop_no = $this->sop_no;
        $version->version = $this->version;
        $version->content = $this->toArray();
        $version->created_at = time();
        $version->updated_at = time();
        $version->save();
    }
}
