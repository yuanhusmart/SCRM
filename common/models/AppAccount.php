<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace common\models;

/**
 * This is the model class for table "app_account".
 *
 * @property int $id 主键ID
 * @property string $app_name 应用名称
 * @property string $app_id 应用ID
 * @property string $app_token 应用TOKEN
 * @property int $data_status 数据状态 0.删除 1.正常
 */
class AppAccount extends \common\db\ActiveRecord
{

    //数据状态 0.删除 1.正常
    const DATA_STATUS_NORMAL = 1;
    const DATA_STATUS_DELETE = 0;

    public static function tableName()
    {
        return '{{app_account}}';
    }

}
