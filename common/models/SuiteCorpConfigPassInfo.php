<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "suite_corp_config_pass_info".
 *
 * @property int $id 主键ID
 * @property int $config_id 关联服务商企业配置表主键ID
 * @property string $ak Access Key
 * @property string $sk Secret Key
 * @property int $status 状态：1.启用 2.禁用
 * @property int $created_at 创建时间
 * @property string $create_userid 创建者用户ID
 * @property int $updated_at 更新时间
 * @property string $update_userid 更新人用户ID
 * @property int $data_status 数据状态 1.正常 2.删除
 */
class SuiteCorpConfigPassInfo extends \yii\db\ActiveRecord
{

    // 数据状态 1.正常 2.删除
    const DATA_STATUS_1 = 1;
    const DATA_STATUS_2 = 2;

    //  状态：1.启用 2.禁用
    const STATUS_1 = 1;
    const STATUS_2 = 2;

    const STATUS_DESC = [
        self::STATUS_1 => '启用',
        self::STATUS_2 => '禁用',
    ];

    const CHANGE_FIELDS = ['config_id', 'ak', 'sk', 'status', 'create_userid', 'update_userid', 'data_status'];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_config_pass_info';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['config_id', 'status', 'created_at', 'updated_at', 'data_status'], 'integer'],
            [['config_id', 'created_at', 'updated_at'], 'default', 'value' => 0],
            ['status', 'default', 'value' => self::STATUS_1],
            ['data_status', 'default', 'value' => self::DATA_STATUS_1],
            [['ak', 'sk'], 'string', 'max' => 100],
            [['create_userid', 'update_userid'], 'string', 'max' => 50],
            [['config_id', 'ak'], 'unique', 'targetAttribute' => ['config_id', 'ak']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'            => 'ID',
            'config_id'     => 'Config ID',
            'ak'            => 'Ak',
            'sk'            => 'Sk',
            'status'        => 'Status',
            'created_at'    => 'Created At',
            'create_userid' => 'Create Userid',
            'updated_at'    => 'Updated At',
            'update_userid' => 'Update Userid',
            'data_status'   => 'Data Status',
        ];
    }

    /**
     * @return bool
     */
    public function beforeValidate()
    {
        $time = time();
        if ($this->isNewRecord) {
            $this->created_at = $time;
        }
        $this->updated_at = $time;
        return parent::beforeValidate();
    }

}
