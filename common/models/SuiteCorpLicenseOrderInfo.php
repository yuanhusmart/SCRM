<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "suite_corp_license_order_info".
 *
 * @property int $id 主键ID
 * @property int $license_order_id 关联服务商接口调用许可订单表主键ID
 * @property string $corp_id 企业ID
 * @property string $sub_order_id 子订单id,可以调用获取订单中的账号列表接口以获取账号列表
 * @property int $account_base_count 基础账号个数，最多1000000个。(若企业为服务商测试企业，最多购买1000个)
 * @property int $account_external_contact_count 互通账号个数，最多1000000个。(若企业为服务商测试企业，最多购买1000个)
 * @property int $account_duration_months 购买的月数，每个月按照31天计算
 * @property int $account_duration_days 购买的天数
 * @property int $auto_active_status 是否开启自动激活，不填默认开启。0：关闭 ，1：开启
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 */
class SuiteCorpLicenseOrderInfo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_license_order_info';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['license_order_id', 'account_base_count', 'account_external_contact_count', 'account_duration_months', 'account_duration_days', 'auto_active_status', 'created_at', 'updated_at'], 'integer'],
            [['license_order_id', 'account_base_count', 'account_external_contact_count', 'account_duration_months', 'account_duration_days', 'auto_active_status', 'created_at', 'updated_at'], 'default', 'value' => 0],
            [['corp_id', 'sub_order_id'], 'string', 'max' => 50],
            [['corp_id', 'sub_order_id'], 'default', 'value' => '']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'                             => 'ID',
            'license_order_id'               => 'License Order ID',
            'corp_id'                        => 'Corp ID',
            'sub_order_id'                   => 'Sub Order ID',
            'account_base_count'             => 'Account Base Count',
            'account_external_contact_count' => 'Account External Contact Count',
            'account_duration_months'        => 'Account Duration Months',
            'account_duration_days'          => 'Account Duration Days',
            'auto_active_status'             => 'Auto Active Status',
            'created_at'                     => 'Created At',
            'updated_at'                     => 'Updated At',
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
