<?php

namespace common\models;

/**
 * 服务商企业朋友圈互动数据表
 * This is the model class for table "suite_corp_moment_comments".
 *
 * @property int $id
 * @property int $corp_moment_id 关联服务商企业朋友圈表主键ID,内部使用
 * @property int $type 类型:1评论 2点赞
 * @property string $userid 联系人userid
 * @property int $create_time 创建时间
 * @property int $is_external 是否外部联系人  1.是 2.否
 */
class SuiteCorpMomentComments extends \common\db\ActiveRecord
{

    // 类型:1评论 2点赞
    const  TYPE_COMMENT_1 = 1;
    const  TYPE_LIKE_2    = 2;

    // 是否外部联系人  1.是 2.否
    const  IS_EXTERNAL_1 = 1;
    const  IS_EXTERNAL_2 = 2;

    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{suite_corp_moment_comments}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['corp_moment_id', 'type', 'create_time', 'is_external'], 'integer'],
            [['corp_moment_id', 'type', 'create_time', 'is_external'], 'default', 'value' => 0],
            ['userid', 'string', 'max' => 50],
            ['userid', 'default', 'value' => ''],
        ];
    }
}
