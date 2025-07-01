<?php

namespace common\models;

/**
 * 服务商企业朋友圈内容表
 * This is the model class for table "suite_corp_moment_contents".
 *
 * @property int $id
 * @property int $corp_moment_id 关联服务商企业朋友圈表主键ID,内部使用
 * @property string $image_media_id 图片的media_id列表，可以通过获取临时素材下载资源
 * @property string $image_media_url 图片的media_id列表，下载后的资源地址
 * @property string $video_media_id 视频media_id列表，可以通过获取临时素材下载资源
 * @property string $video_media_url 视频media_id列表，下载后的资源地址
 * @property string $video_thumb_media_id 视频封面media_id列表，可以通过获取临时素材下载资源
 * @property string $video_thumb_media_url 视频封面media_id列表，下载后的资源地址
 * @property string $link_title 网页链接标题
 * @property string $link_url 网页链接url
 * @property float $location_latitude 地理位置纬度
 * @property float $location_longitude 地理位置经度
 * @property string $location_name 地理位置名称
 */
class SuiteCorpMomentContents extends \common\db\ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{suite_corp_moment_contents}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['location_latitude', 'location_longitude'], 'number'],
            ['corp_moment_id', 'integer'],
            [['corp_moment_id', 'location_latitude', 'location_longitude'], 'default', 'value' => 0],
            [['image_media_id', 'video_media_id', 'video_thumb_media_id'], 'string', 'max' => 50],
            ['location_name', 'string', 'max' => 200],
            [['video_media_url', 'video_thumb_media_url', 'image_media_url'], 'string', 'max' => 250],
            [['link_title', 'link_url'], 'string', 'max' => 500],
            [['image_media_id', 'video_media_id', 'video_thumb_media_id', 'location_name', 'video_media_url', 'video_thumb_media_url', 'image_media_url', 'link_title', 'link_url'], 'default', 'value' => ''],
        ];
    }
}
