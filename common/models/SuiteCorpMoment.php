<?php

namespace common\models;

/**
 * 服务商企业朋友圈表
 * This is the model class for table "suite_corp_moment".
 *
 * @property int $id
 * @property string $suite_id 服务商ID
 * @property string $corp_id 企业ID
 * @property string $creator 朋友圈创建者userid，企业发表内容到客户的朋友圈接口创建的朋友圈不再返回该字段
 * @property string $moment_id 朋友圈id
 * @property int $create_time 创建时间
 * @property int $create_type 朋友圈创建来源。0：企业 1：个人
 * @property int $visible_type 可见范围类型。0：部分可见 1：公开
 * @property int $comment_count 评论数量
 * @property int $like_count 点赞数量
 * @property string $content 文本内容
 */
class SuiteCorpMoment extends \common\db\ActiveRecord
{

    // 朋友圈创建来源。0：企业 1：个人
    const CREATE_TYPE_0 = 0;
    const CREATE_TYPE_1 = 1;

    // 可见范围类型。0：部分可见 1：公开
    const VISIBLE_TYPE_0 = 0;
    const VISIBLE_TYPE_1 = 1;

    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{suite_corp_moment}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['create_time', 'create_type', 'visible_type', 'comment_count', 'like_count'], 'integer'],
            [['create_time', 'create_type', 'visible_type', 'comment_count', 'like_count'], 'default', 'value' => 0],
            [['suite_id', 'corp_id', 'creator', 'moment_id'], 'string', 'max' => 50],
            ['content', 'string', 'max' => 1000],
            [['suite_id', 'corp_id', 'creator', 'moment_id', 'content'], 'default', 'value' => '']
        ];
    }

    /**
     * 关联服务商企业朋友圈互动数据表
     */
    public function getMomentComments()
    {
        return $this->hasMany(SuiteCorpMomentComments::className(), ['corp_moment_id' => 'id']);
    }

    /**
     * 关联服务商企业朋友圈内容表
     */
    public function getMomentContents()
    {
        return $this->hasMany(SuiteCorpMomentContents::className(), ['corp_moment_id' => 'id']);
    }

    /**
     * @return array
     */
    public function getItemsData()
    {
        $data                   = $this->toArray();
        $data['momentContents'] = empty($this->momentContents) ? null : $this->momentContents;
        return $data;
    }
}
