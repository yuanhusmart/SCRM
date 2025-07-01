<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "suite_corp_license_active_info".
 *
 * @property int $id 主键ID
 * @property int $license_order_id 关联服务商接口调用许可订单表主键ID
 * @property int $license_order_info_id 关联服务商接口调用许可订单详情表主键ID
 * @property string $suite_id 服务商ID
 * @property string $corp_id 企业ID
 * @property string $order_id 订单id
 * @property string $active_code 激活码
 * @property int $type 账号类型：1:基础账号，2:互通账号
 * @property int $status 账号状态：1: 未绑定 2: 已绑定且有效 3: 已过期 4: 待转移 5: 已合并 6: 已分配给下游
 * @property int $is_auto_auth 自动授权 1.开启 2.关闭
 * @property string $userid 账号绑定激活的企业成员userid，未激活则不返回该字段。返回加密的userid
 * @property int $create_time 创建时间，订单支付成功后立即创建。
 * @property int $active_time 首次激活绑定用户的时间，未激活则不返回该字段
 * @property int $updated_active_time 最新激活绑定用户的时间
 * @property int $expire_time 过期时间。为首次激活绑定的时间加上购买时长。未激活则不返回该字段
 * @property string $merge_info 合并信息，合并的激活码或者被合并的激活码才返回该字段。
 * @property string $share_info 分配信息，当激活码在上下游/企业互联场景下，从上游分配给下游时，获取上游或者下游企业该激活码详情时返回
 * @property int $created_at 创建时间
 * @property int $updated_at 更新时间
 */
class SuiteCorpLicenseActiveInfo extends \yii\db\ActiveRecord
{

    const STATUS_1 = 1;
    const STATUS_2 = 2;
    const STATUS_3 = 3;
    const STATUS_4 = 4;
    const STATUS_5 = 5;
    const STATUS_6 = 6;

    const STATUS_DESC = [
        self::STATUS_1 => '未绑定',
        self::STATUS_2 => '已绑定且有效',
        self::STATUS_3 => '已过期',
        self::STATUS_4 => '待转移',
        self::STATUS_5 => '已合并',
        self::STATUS_6 => '已分配给下游',
    ];

    const TYPE_1 = 1;
    const TYPE_2 = 2;

    const TYPE_DESC = [
        self::TYPE_1 => '基础账号',
        self::TYPE_2 => '互通账号',
    ];

    // 自动授权 1.开启 2.关闭
    const ACTIVE_IS_AUTO_AUTH_1 = 1;
    const ACTIVE_IS_AUTO_AUTH_2 = 2;

    const ACTIVE_IS_AUTO_AUTH_DESC = [
        self::ACTIVE_IS_AUTO_AUTH_1 => '开启',
        self::ACTIVE_IS_AUTO_AUTH_2 => '关闭',
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'suite_corp_license_active_info';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['license_order_id', 'license_order_info_id', 'type', 'status', 'is_auto_auth', 'create_time', 'active_time', 'updated_active_time', 'expire_time', 'created_at', 'updated_at'], 'integer'],
            [['license_order_id', 'license_order_info_id', 'type', 'status', 'create_time', 'active_time', 'updated_active_time', 'expire_time', 'created_at', 'updated_at'], 'default', 'value' => 0],
            [['is_auto_auth'], 'default', 'value' => self::ACTIVE_IS_AUTO_AUTH_1],
            [['suite_id', 'corp_id', 'order_id', 'active_code', 'userid'], 'string', 'max' => 50],
            [['merge_info', 'share_info'], 'string', 'max' => 500],
            [['suite_id', 'corp_id', 'order_id', 'active_code', 'userid', 'merge_info', 'share_info'], 'default', 'value' => ''],
            [['corp_id', 'active_code'], 'unique', 'targetAttribute' => ['corp_id', 'active_code']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'                    => 'ID',
            'license_order_id'      => 'License Order ID',
            'license_order_info_id' => 'License Order Info ID',
            'suite_id'              => 'Suite ID',
            'corp_id'               => 'Corp ID',
            'order_id'              => 'Order ID',
            'active_code'           => 'Active Code',
            'type'                  => 'Type',
            'status'                => 'Status',
            'is_auto_auth'          => 'Is Auto Auth',
            'userid'                => 'Userid',
            'create_time'           => 'Create Time',
            'active_time'           => 'Active Time',
            'updated_active_time'   => 'Updated Active Time',
            'expire_time'           => 'Expire Time',
            'merge_info'            => 'Merge Info',
            'share_info'            => 'Share Info',
            'created_at'            => 'Created At',
            'updated_at'            => 'Updated At',
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
     * @return \yii\db\ActiveQuery
     */
    public function getAccountInfo()
    {
        return $this->hasOne(Account::className(), ['suite_id' => 'suite_id', 'corp_id' => 'corp_id', 'userid' => 'userid'])->select('id,suite_id,corp_id,userid,jnumber,nickname,status,deleted_at');
    }

}
