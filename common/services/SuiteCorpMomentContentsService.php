<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpMomentContents;

/**
 * Class SuiteCorpMomentContentsService
 * @package common\services
 */
class SuiteCorpMomentContentsService extends Service
{

    const MQ_MOMENT_MEDIA_EXCHANGE    = 'aaw.moment.media.handle.dir.ex';
    const MQ_MOMENT_MEDIA_QUEUE       = 'aaw.moment.media.handle.que';
    const MQ_MOMENT_MEDIA_ROUTING_KEY = 'aaw.moment.media.handle.rk';

    /**
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     */
    public static function create($params)
    {
        $corpMomentId = self::getInt($params, 'corp_moment_id');
        if (empty($corpMomentId)) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $attributes = [];
        if (!empty($params['image'])) {
            foreach ($params['image'] as $img) {
                $attributes[] = ['corp_moment_id' => $corpMomentId, 'image_media_id' => $img['media_id']];
            }
        } elseif (!empty($params['video'])) {
            $attributes[] = ['corp_moment_id' => $corpMomentId, 'video_media_id' => $params['video']['media_id'], 'video_thumb_media_id' => $params['video']['thumb_media_id']];
        } elseif (!empty($params['link'])) {
            $attributes[] = ['corp_moment_id' => $corpMomentId, 'link_url' => $params['link']['url'], 'link_title' => $params['link']['title']];

        } elseif (!empty($params['location'])) {
            $attributes[] = [
                'corp_moment_id'     => $corpMomentId,
                'location_latitude'  => $params['location']['latitude'],
                'location_longitude' => $params['location']['longitude'],
                'location_name'      => $params['location']['name']
            ];
        }

        $mqData = [];
        foreach ($attributes as $item) {
            $create = new SuiteCorpMomentContents();
            $create->load($item, '');
            //校验参数
            if (!$create->validate()) {
                throw new ErrException(Code::PARAMS_ERROR, $create->getError());
            }
            if (!$create->save()) {
                throw new ErrException(Code::CREATE_ERROR, $create->getError());
            }
            $momentContentsId = $create->getPrimaryKey();
            if (!empty($item['image_media_id'])) {
                $mqData[] = ['id' => $momentContentsId, 'type' => 'image_media_id', 'field' => 'image_media_url', 'media_id' => $item['image_media_id']];
            } elseif (!empty($item['video_media_id'])) {
                $mqData[] = ['id' => $momentContentsId, 'type' => 'video_media_id', 'field' => 'video_media_url', 'media_id' => $item['video_media_id']];
            } elseif (!empty($item['video_thumb_media_id'])) {
                $mqData[] = ['id' => $momentContentsId, 'type' => 'video_thumb_media_id', 'field' => 'video_thumb_media_url', 'media_id' => $item['video_thumb_media_id']];
            }
        }
        // 批量推送到MQ
        if ($mqData) {
            $exchange   = self::MQ_MOMENT_MEDIA_EXCHANGE;
            $queue      = self::MQ_MOMENT_MEDIA_QUEUE;
            $routingKey = self::MQ_MOMENT_MEDIA_ROUTING_KEY;
            self::pushRabbitMQMsg($exchange, $queue, function ($mq) use ($mqData, $routingKey) {
                try {
                    foreach ($mqData as $msg) {
                        $mq->publish(json_encode($msg, JSON_UNESCAPED_UNICODE), $routingKey);
                    }
                } catch (\Exception $e) {
                    \Yii::warning($e->getMessage());
                }
            }, $routingKey);
        }
        return $corpMomentId;
    }

    /**
     * @param $params
     * @return true
     * @throws ErrException
     */
    public static function update($params)
    {
        $id = self::getId($params);
        if (!$id) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data = SuiteCorpMomentContents::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        $attributes = self::includeKeys($params, ['corp_moment_id', 'video_media_id', 'video_media_url', 'video_thumb_media_id', 'video_thumb_media_url', 'link_title', 'link_url', 'location_latitude', 'location_longitude', 'location_name', 'image_media_id', 'image_media_url']);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data->attributes = $attributes;
        if (!$data->save()) {
            throw new ErrException(Code::UPDATE_ERROR, $data->getError());
        }
        return true;
    }

    /**
     * @param $params
     * @return true
     * @throws ErrException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public static function delete($params)
    {
        $id = self::getId($params);
        if (empty($id)) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data = SuiteCorpMomentContents::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        if (!$data->delete()) {
            throw new ErrException(Code::DATA_ERROR);
        }
        return true;
    }

    /**
     * @param $corpMomentId
     * @return int
     */
    public static function deleteAll($corpMomentId)
    {
        return SuiteCorpMomentContents::deleteAll(['corp_moment_id' => $corpMomentId]);
    }

}
