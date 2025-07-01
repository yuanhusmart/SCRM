<?php

namespace common\models;

use common\models\concerns\traits\Helper;

/**
 * 系统文件表
 *
 * @property int $id
 * @property int $account_id  int          default 0  not null comment '上传帐号ID=suite_corp_accounts.id',
 * @property string $name        varchar(255) default '' not null comment '文件名称',
 * @property string $path        varchar(255) default '' not null comment '存储路径',
 * @property string $ext         varchar(255) default '' not null comment '文件后缀',
 * @property int $size        int          default 0  not null comment '文件大小',
 * @property int $belong_id   bigint       default 0  not null comment '多态关联ID',
 * @property string $belong_type varchar(255) default '' not null comment '多态关联模型',
 * @property string $tag         varchar(255) default '' not null comment '标签',
 * @property int $created_at  int          default 0  not null comment '创建时间',
 * @property int $updated_at  int          default 0  not null comment '更新时间',
 */
class SuiteFile extends \common\db\ActiveRecord
{
    use Helper;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_files';
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'          => 'ID',
            'account_id'  => 'Account ID',
            'name'        => 'Name',
            'path'        => 'Path',
            'ext'         => 'Ext',
            'size'        => 'Size',
            'belong_id'   => 'Belong ID',
            'belong_type' => 'Belong Type',
            'tag'         => 'Tag',
            'created_at'  => 'Created At',
            'updated_at'  => 'Updated At',
        ];
    }

    public function fields()
    {
        $fields = parent::fields();

        $fields['full_path'] = function ($model) {
            return $model->getFullPath();
        };

        return $fields;
    }

    public function getFullPath()
    {
        return trim(\Yii::$app->params['oss']['default'],'/'). '/' . trim($this->path,'/');
    }
}
