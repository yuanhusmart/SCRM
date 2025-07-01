<?php

namespace common\models;

/**
 * 服务商企业外部联系人关注用户标签表
 * This is the model class for table "suite_corp_external_contact_follow_user_tags".
 *
 * @property int $id 主键ID
 * @property int $external_contact_id 关联服务商企业外部联系人表主键ID，内部使用关联关系ID
 * @property int $external_contact_follow_user_id 关联服务商企业外部联系人关注用户表主键ID，内部使用关联关系ID
 * @property string $group_name 该成员添加此外部联系人所打标签的分组名称
 * @property string $tag_name 该成员添加此外部联系人所打标签名称
 * @property int $type 该成员添加此外部联系人所打标签类型, 1-企业设置，2-用户自定义，3-规则组标签
 * @property string $tag_id 该成员添加此外部联系人所打企业标签的id，用户自定义类型标签（type=2）不返回
 */
class SuiteCorpExternalContactFollowUserTags extends \common\db\ActiveRecord
{

    /** @var string[] 该成员添加此外部联系人所打标签类型 */
    const ENUM_TAGS_TYPE = [
        1 => '企业设置',
        2 => '用户自定义',
        3 => '规则组标签',
    ];

    /**
     * external_contact_follow_user_id : 关联服务商企业外部联系人关注用户表主键ID，内部使用关联关系ID
     * @return string
     */
    public static function tableName()
    {
        return '{{suite_corp_external_contact_follow_user_tags}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['external_contact_id', 'external_contact_follow_user_id', 'type'], 'integer'],
            [['external_contact_follow_user_id', 'type'], 'default', 'value' => 0],
            [['group_name', 'tag_name', 'tag_id'], 'string', 'max' => 255],
            [['group_name', 'tag_name', 'tag_id'], 'default', 'value' => ''],
        ];
    }

}
