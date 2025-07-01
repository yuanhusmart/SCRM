<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "suite_corp_license_order".
 *
 * @property int $id 主键ID
 * @property string $suite_id 服务商ID
 * @property string $order_id 订单id
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 * @property int $order_type 订单类型,1.购买账号,2.续期账号,5.应用版本付费迁移订单,6.历史合同迁移订单,8.多企业新购订单
 * @property int $order_status 订单状态,0.待支付,1.已支付,2.已取消（未支付,订单已关闭）3.未支付,订单已过期,4.申请退款中,5.退款成功,6.退款被拒绝,7.订单已失效
 * @property int $price 订单金额,单位分
 * @property int $create_time 订单创建时间
 * @property int $pay_time 支付时间
 */
class SuiteCorpLicenseOrder extends \yii\db\ActiveRecord
{
    const ORDER_TYPE_1 = 1;
    const ORDER_TYPE_2 = 2;
    const ORDER_TYPE_5 = 5;
    const ORDER_TYPE_6 = 6;
    const ORDER_TYPE_8 = 8;

    // 订单类型,1.购买账号,2.续期账号,5.应用版本付费迁移订单,6.历史合同迁移订单,8.多企业新购订单
    const ORDER_TYPE_DESC = [
        self::ORDER_TYPE_1 => '购买账号',
        self::ORDER_TYPE_2 => '续期账号',
        self::ORDER_TYPE_5 => '应用版本付费迁移订单',
        self::ORDER_TYPE_6 => '历史合同迁移订单',
        self::ORDER_TYPE_8 => '多企业新购订单',
    ];

    // 订单状态,0.待支付,1.已支付,2.已取消（未支付,订单已关闭）3.未支付,订单已过期,4.申请退款中,5.退款成功,6.退款被拒绝,7.订单已失效
    const ORDER_STATUS_0 = 0;
    const ORDER_STATUS_1 = 1;
    const ORDER_STATUS_2 = 2;
    const ORDER_STATUS_3 = 3;
    const ORDER_STATUS_4 = 4;
    const ORDER_STATUS_5 = 5;
    const ORDER_STATUS_6 = 6;
    const ORDER_STATUS_7 = 7;

    const ORDER_STATUS_DESC = [
        self::ORDER_STATUS_0 => '待支付',
        self::ORDER_STATUS_1 => '已支付',
        self::ORDER_STATUS_2 => '已取消',
        self::ORDER_STATUS_3 => '未支付,订单已过期',
        self::ORDER_STATUS_4 => '申请退款中',
        self::ORDER_STATUS_5 => '退款成功',
        self::ORDER_STATUS_6 => '退款被拒绝',
        self::ORDER_STATUS_7 => '订单已失效',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_license_order';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'updated_at', 'order_type', 'order_status', 'price', 'create_time', 'pay_time'], 'integer'],
            [['created_at', 'updated_at', 'order_type', 'order_status', 'price', 'create_time', 'pay_time'], 'default', 'value' => 0],
            [['suite_id', 'order_id'], 'string', 'max' => 50],
            [['suite_id', 'order_id'], 'default', 'value' => '']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'           => 'ID',
            'suite_id'     => 'Suite ID',
            'order_id'     => 'Order ID',
            'created_at'   => 'Created At',
            'updated_at'   => 'Updated At',
            'order_type'   => 'Order Type',
            'order_status' => 'Order Status',
            'price'        => 'Price',
            'create_time'  => 'Create Time',
            'pay_time'     => 'Pay Time',
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

    /**
     * 关联服务商接口调用许可订单详情表
     */
    public function getLicenseOrderInfos()
    {
        return $this->hasMany(SuiteCorpLicenseOrderInfo::className(), ['license_order_id' => 'id']);
    }

    /**
     * @return array
     */
    public function getItemsData()
    {
        $data                      = $this->toArray();
        $data['licenseOrderInfos'] = empty($this->licenseOrderInfos) ? null : $this->licenseOrderInfos;
        return $data;
    }
}