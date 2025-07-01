<?php

namespace common\models;

/**
 * 服务商企业外部联系人关注用户手机号表
 * This is the model class for table "suite_corp_external_contact_follow_user_remark_mobiles".
 *
 * @property int $id 主键ID
 * @property int $external_contact_follow_user_id 关联服务商企业外部联系人关注用户表主键ID，内部使用关联关系ID
 * @property string $mobiles 该成员对此客户备注的手机号码，代开发自建应用需要管理员授权才可以获取，第三方不可获取，上游企业不可获取下游企业客户该字段
 */
class SuiteCorpExternalContactFollowUserRemarkMobiles extends \common\db\ActiveRecord
{

    /**
     * external_contact_follow_user_id : 关联服务商企业外部联系人关注用户表主键ID，内部使用关联关系ID
     * @return string
     */
    public static function tableName()
    {
        return '{{suite_corp_external_contact_follow_user_remark_mobiles}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['external_contact_follow_user_id', 'integer'],
            ['mobiles', 'string', 'max' => 255, 'min' => 1],
        ];
    }

}
