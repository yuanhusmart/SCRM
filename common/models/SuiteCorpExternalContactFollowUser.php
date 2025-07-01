<?php

namespace common\models;

/**
 * 服务商企业外部联系人关注用户表
 * This is the model class for table "suite_corp_external_contact_follow_user".
 *
 * @property int $id 主键ID
 * @property int $external_contact_id 关联服务商企业外部联系人表主键ID，内部使用关联关系ID
 * @property string $userid 添加了此外部联系人的企业成员userid
 * @property string $remark 该成员对此外部联系人的备注
 * @property string $description 该成员对此外部联系人的描述
 * @property int $createtime 该成员添加此外部联系人的时间
 * @property string $remark_corp_name 该成员对此微信客户备注的企业名称（仅微信客户有该字段）
 * @property int $add_way 该成员添加此客户的来源，具体含义详见来源定义
 * @property string $wechat_channels_nickname 该成员添加此客户的来源add_way为10时，对应的视频号名称
 * @property int $wechat_channels_source 视频号添加场景，0-未知 1-视频号主页 2-视频号直播间 3-视频号留资服务
 * @property string $oper_userid 发起添加的userid，如果成员主动添加，为成员的userid；如果是客户主动添加，则为客户的外部联系人userid；如果是内部成员共享/管理员分配，则为对应的成员/管理员userid
 * @property string $state 企业自定义的state参数，用于区分客户具体是通过哪个「联系我」或获客链接添加；由企业通过创建「联系我」或在获客链接中添加customer_channel参数进行指定
 * @property int $updated_at 更新时间
 * @property int $deleted_at 删除时间
 */
class SuiteCorpExternalContactFollowUser extends \common\db\ActiveRecord
{

    // 添加客户的来源
    const ENUM_ADD_WAY = [
        0   => '未知来源',
        1   => '扫描二维码',
        2   => '搜索手机号',
        3   => '名片分享',
        4   => '群聊',
        5   => '手机通讯录',
        6   => '微信联系人',
        8   => '安装第三方应用时自动添加的客服人员',
        9   => '搜索邮箱',
        10  => '视频号添加',
        11  => '通过日程参与人添加',
        12  => '通过会议参与人添加',
        13  => '添加微信好友对应的企业微信',
        14  => '通过智慧硬件专属客服添加',
        15  => '通过上门服务客服添加',
        16  => '通过获客链接添加',
        17  => '通过定制开发添加',
        18  => '通过需求回复添加',
        201 => '内部成员共享',
        202 => '管理员/负责人分配',
    ];

    /**
     * external_contact_id : 关联服务商企业外部联系人表主键ID，内部使用关联关系ID
     * @return string
     */
    public static function tableName()
    {
        return '{{suite_corp_external_contact_follow_user}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['external_contact_id', 'createtime', 'updated_at', 'deleted_at', 'add_way', 'wechat_channels_source'], 'integer'],
            [['external_contact_id', 'createtime', 'updated_at', 'deleted_at', 'add_way', 'wechat_channels_source'], 'default', 'value' => 0],
            ['userid', 'string', 'max' => 50],
            [['oper_userid', 'state'], 'string', 'max' => 100],
            [['remark', 'description', 'remark_corp_name', 'wechat_channels_nickname'], 'string', 'max' => 255],
            [['userid', 'oper_userid', 'state', 'remark', 'description', 'remark_corp_name', 'wechat_channels_nickname'], 'default', 'value' => ''],
        ];
    }

    public function beforeValidate()
    {
        $this->updated_at = time();
        return parent::beforeValidate();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getFollowUserTags()
    {
        return $this->hasMany(SuiteCorpExternalContactFollowUserTags::class, ['id' => 'external_contact_follow_user_id']);
    }


}
