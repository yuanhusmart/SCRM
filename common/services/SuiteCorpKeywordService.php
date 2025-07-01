<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpKeyword;

/**
 * Class SuiteCorpKeywordService
 * @package common\services
 */
class SuiteCorpKeywordService extends Service
{

    /**
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     */
    public static function create($params)
    {
        if (empty($params['suite_id']) || empty($params['corp_id'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        if (empty($params['word_list'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            foreach ($params['word_list'] as $word) {
                $attributes = ['suite_id' => $params['suite_id'], 'corp_id' => $params['corp_id'], 'word' => $word];
                $create     = SuiteCorpKeyword::find()->where($attributes)->one();
                if (empty($create)) {
                    $create = new SuiteCorpKeyword();
                }
                $create->load($attributes, '');
                //校验参数
                if (!$create->validate()) {
                    throw new ErrException(Code::PARAMS_ERROR, $create->getErrors());
                }
                if (!$create->save()) {
                    throw new ErrException(Code::CREATE_ERROR, $create->getErrors());
                }
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        SuiteCorpRuleService::syncRuleByChangeWord($params['suite_id'], $params['corp_id']);
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
        if (!$id) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $data = SuiteCorpKeyword::findOne($id);
            if (!$data) {
                throw new ErrException(Code::NOT_EXIST);
            }
            if (!$data->delete()) {
                throw new ErrException(Code::DELETE_ERROR, $data->getErrors());
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        SuiteCorpRuleService::syncRuleByChangeWord($data->suite_id, $data->corp_id);
        return true;
    }

    /**
     * @param $params
     * @return array
     * @throws ErrException
     */
    public static function items($params)
    {
        list($page, $per_page) = self::getPageInfo($params);
        $suiteId = self::getString($params, 'suite_id');
        $corpId  = self::getString($params, 'corp_id');

        if (!$suiteId || !$corpId) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        $query = SuiteCorpKeyword::find()
                                 ->andWhere(["suite_id" => $suiteId])
                                 ->andWhere(["corp_id" => $corpId]);

        // 关键词
        if ($word = self::getString($params, 'word')) {
            $query->andWhere(["word" => $word]);
        }

        $total  = $query->count();
        $offset = ($page - 1) * $per_page;
        $resp   = [];
        if ($total > 0) {
            $resp = $query->orderBy(['id' => SORT_DESC])->offset($offset)->limit($per_page)->asArray()->all();
        }
        return [
            'Keyword'    => $resp,
            'pagination' => [
                'page'     => $page,
                'per_page' => $per_page,
                'total'    => intval($total)
            ]
        ];
    }

}
