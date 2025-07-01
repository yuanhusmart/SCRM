<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpMoment;
use common\models\SuiteCorpMomentComments;

/**
 * Class SuiteCorpMomentCommentsService
 * @package common\services
 */
class SuiteCorpMomentCommentsService extends Service
{

    const CHANGE_FIELDS = ['corp_moment_id', 'type', 'external_userid', 'create_time', 'is_external', 'userid'];

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
        $attributes['is_external'] = SuiteCorpMomentComments::IS_EXTERNAL_2;
        if (!empty($attributes['external_userid'])) {
            $attributes['is_external'] = SuiteCorpMomentComments::IS_EXTERNAL_1;
            $attributes['userid']      = $attributes['external_userid'];
            unset($attributes['external_userid']);
        }
        $create = new SuiteCorpMomentComments();
        $create->load($attributes, '');
        //校验参数
        if (!$create->validate()) {
            throw new ErrException(Code::PARAMS_ERROR, $create->getError());
        }
        if (!$create->save()) {
            throw new ErrException(Code::CREATE_ERROR, $create->getError());
        }
        return $create->getPrimaryKey();
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
        $data = SuiteCorpMomentComments::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        $attributes = self::includeKeys($params, self::CHANGE_FIELDS);
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
        $data = SuiteCorpMomentComments::findOne($id);
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
        return SuiteCorpMomentComments::deleteAll(['corp_moment_id' => $corpMomentId]);
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
        $corpMomentId = self::getInt($params, 'corp_moment_id');
        if (!$corpMomentId) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $moment = SuiteCorpMoment::find()
                                 ->select('id,suite_id,corp_id,moment_id,creator')
                                 ->andWhere(["id" => $corpMomentId])
                                 ->asArray()
                                 ->limit(1)
                                 ->one();
        if (empty($moment)) {
            throw new ErrException(Code::DATA_ERROR);
        }
        try {
            $moment['comments']      = SuiteService::getExternalContactMomentComments($moment['suite_id'], $moment['corp_id'], ['moment_id' => $moment['moment_id'], 'userid' => $moment['creator']]);
            $moment['comment_count'] = empty($moment['comments']['comment_list']) ? 0 : count($moment['comments']['comment_list']);
            $moment['like_count']    = empty($moment['comments']['like_list']) ? 0 : count($moment['comments']['like_list']);
            SuiteCorpMomentService::update($moment);
        } catch (\Exception $momentE) {
            throw new ErrException($momentE);
        }
        $query = SuiteCorpMomentComments::find()->andWhere(["corp_moment_id" => $corpMomentId]);
        if ($type = self::getInt($params, 'type')) {
            $query->andWhere(['type' => $type]);
        }
        $total  = $query->count();
        $offset = ($page - 1) * $per_page;
        $resp   = $query->orderBy(['create_time' => SORT_DESC])->offset($offset)->asArray()->limit($per_page)->all();
        return [
            'MomentComments' => $resp,
            'pagination'     => [
                'page'     => $page,
                'per_page' => $per_page,
                'total'    => intval($total)
            ]
        ];
    }
}
