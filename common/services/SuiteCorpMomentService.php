<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpMoment;
use common\models\SuiteCorpMomentComments;

/**
 * Class SuiteCorpMomentService
 * @package common\services
 */
class SuiteCorpMomentService extends Service
{

    const CHANGE_FIELDS = ['suite_id', 'corp_id', 'creator', 'moment_id', 'create_time', 'create_type', 'visible_type', 'comment_count', 'like_count'];

    /**
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     */
    public static function create($params)
    {
        $attributes = self::includeKeys($params, self::CHANGE_FIELDS);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        $attributes['content'] = empty($params['text']) ? '' : $params['text']['content'];
        $transaction           = \Yii::$app->db->beginTransaction();
        try {
            $corpMoment = SuiteCorpMoment::findOne(['suite_id' => $attributes['suite_id'] ?? '', 'corp_id' => $attributes['corp_id'] ?? '', 'creator' => $attributes['creator'] ?? '', 'moment_id' => $attributes['moment_id'] ?? '']);
            // 如果数据不存在 写入主表 + 媒体数据
            if (empty($corpMoment)) {
                $corpMoment = new SuiteCorpMoment();
                $corpMoment->load($attributes, '');
                // 校验参数
                if (!$corpMoment->validate()) {
                    throw new ErrException(Code::PARAMS_ERROR, $corpMoment->getError());
                }
                if (!$corpMoment->save()) {
                    throw new ErrException(Code::CREATE_ERROR, $corpMoment->getError());
                }
                $corpMomentId             = $corpMoment->getPrimaryKey();
                $params['corp_moment_id'] = $corpMomentId;
                SuiteCorpMomentContentsService::create($params);
            } else {
                $corpMomentId              = $corpMoment->id;
                $corpMoment->comment_count = empty($params['comment_count']) ? $corpMoment->comment_count : $params['comment_count'];
                $corpMoment->like_count    = empty($params['like_count']) ? $corpMoment->like_count : $params['like_count'];
                if (!$corpMoment->save()) {
                    throw new ErrException(Code::UPDATE_ERROR, $corpMoment->getError());
                }
                SuiteCorpMomentCommentsService::deleteAll($corpMomentId);
            }
            if (!empty($params['comments']['comment_list'])) {
                foreach ($params['comments']['comment_list'] as $comment) {
                    $comment['type']           = SuiteCorpMomentComments::TYPE_COMMENT_1;
                    $comment['corp_moment_id'] = $corpMomentId;
                    SuiteCorpMomentCommentsService::create($comment);
                }
            }
            if (!empty($params['comments']['like_list'])) {
                foreach ($params['comments']['like_list'] as $like) {
                    $like['type']           = SuiteCorpMomentComments::TYPE_LIKE_2;
                    $like['corp_moment_id'] = $corpMomentId;
                    SuiteCorpMomentCommentsService::create($like);
                }
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        return $corpMomentId;
    }

    /**
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     */
    public static function update($params)
    {
        $id = self::getId($params);
        if (!$id) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $corpMoment = SuiteCorpMoment::findOne($id);
            if (!$corpMoment) {
                throw new ErrException(Code::DATA_ERROR);
            }
            $corpMoment->comment_count = empty($params['comment_count']) ? $corpMoment->comment_count : $params['comment_count'];
            $corpMoment->like_count    = empty($params['like_count']) ? $corpMoment->like_count : $params['like_count'];
            if (!$corpMoment->save()) {
                throw new ErrException(Code::CREATE_ERROR, $corpMoment->getError());
            }
            SuiteCorpMomentCommentsService::deleteAll($id);
            if (!empty($params['comments']['comment_list'])) {
                foreach ($params['comments']['comment_list'] as $comment) {
                    $comment['type']           = SuiteCorpMomentComments::TYPE_COMMENT_1;
                    $comment['corp_moment_id'] = $id;
                    SuiteCorpMomentCommentsService::create($comment);
                }
            }
            if (!empty($params['comments']['like_list'])) {
                foreach ($params['comments']['like_list'] as $like) {
                    $like['type']           = SuiteCorpMomentComments::TYPE_LIKE_2;
                    $like['corp_moment_id'] = $id;
                    SuiteCorpMomentCommentsService::create($like);
                }
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        return $id;
    }

    /**
     * @param $params
     * @return array
     * @throws ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public static function items($params)
    {
        list($page, $per_page) = self::getPageInfo($params);
        $suiteId = self::getString($params, 'suite_id');
        $corpId  = self::getString($params, 'corp_id');
        if (!$suiteId || !$corpId) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $query = SuiteCorpMoment::find()->andWhere(["suite_id" => $suiteId])->andWhere(["corp_id" => $corpId]);
        if ($creator = self::getString($params, 'creator')) {
            $query->andWhere(["creator" => $creator]);
        }
        $total  = $query->count();
        $offset = ($page - 1) * $per_page;
        $resp   = [];
        if ($total > 0) {
            $resp = $query->orderBy(['create_time' => SORT_DESC])->offset($offset)->limit($per_page)->all();
        }
        $data = [
            'Moment'     => [],
            'pagination' => [
                'page'     => $page,
                'per_page' => $per_page,
                'total'    => intval($total)
            ]
        ];
        foreach ($resp as $value) {
            $data['Moment'][] = $value->getItemsData();
        }
        return $data;
    }

}
