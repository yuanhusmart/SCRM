<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpProgramHitNextCursor;

class SuiteCorpProgramHitNextCursorService extends Service
{

    /**
     * @param $suiteId
     * @param $corpId
     * @param $nextCursor
     * @return true
     * @throws ErrException
     */
    public static function updateNextCursorByCorpId($suiteId, $corpId, $nextCursor = '')
    {
        $data = SuiteCorpProgramHitNextCursor::findOne(['suite_id' => $suiteId, 'corp_id' => $corpId]);
        if (!$data) {
            $data = new SuiteCorpProgramHitNextCursor();
        }
        $data->suite_id    = $suiteId;
        $data->corp_id     = $corpId;
        $data->next_cursor = $nextCursor;
        if (!$data->save()) {
            throw new ErrException(Code::UPDATE_ERROR, $data->getError());
        }
        return true;
    }

    /**
     * 根据服务商ID、企业ID获取消息游标
     * @param $suiteId
     * @param $corpId
     * @return false|string|null
     */
    public static function getNextCursorByCorpId($suiteId, $corpId)
    {
        $redisKey   = \Yii::$app->params["redisPrefix"] . 'program.hit.next.cursor.' . $suiteId . '.' . $corpId;
        $nextCursor = \Yii::$app->redis->get($redisKey);
        if (empty($nextCursor)) {
            $nextCursor = SuiteCorpProgramHitNextCursor::find()->where(['suite_id' => $suiteId, 'corp_id' => $corpId])->select('next_cursor')->scalar();
        }
        return $nextCursor;
    }

    /**
     * 根据服务商ID、企业ID设置消息游标
     * @param $suiteId
     * @param $corpId
     * @param $nextCursor
     * @return mixed
     * @throws ErrException
     */
    public static function setNextCursorByCorpId($suiteId, $corpId, $nextCursor)
    {
        $redisKey = \Yii::$app->params["redisPrefix"] . 'program.hit.next.cursor.' . $suiteId . '.' . $corpId;
        $redis    = \Yii::$app->redis;
        $redis->set($redisKey, $nextCursor);
        $redis->expire($redisKey, 86400); //1内有效
        // 更新企业消息Seq
        self::updateNextCursorByCorpId($suiteId, $corpId, $nextCursor);
        return $nextCursor;
    }

}
