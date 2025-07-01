<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpSemantics;

/**
 * Class SuiteCorpSemanticsService
 * @package common\services
 */
class SuiteCorpSemanticsService extends Service
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

        if (empty($params['semantics_list'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        foreach ($params['semantics_list'] as $semantics) {
            $attributes = ['suite_id' => $params['suite_id'], 'corp_id' => $params['corp_id'], 'semantics' => $semantics];
            $create     = SuiteCorpSemantics::find()->where($attributes)->one();
            if (empty($create)) {
                $create = new SuiteCorpSemantics();
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
        return true;
    }

    /**
     * @param $suiteId
     * @param $corpId
     * @param $semanticsList
     * @return int
     * @throws \yii\db\Exception
     */
    public static function batchInsertSemantics($suiteId, $corpId, $semanticsList)
    {
        $insertData = [];
        foreach ($semanticsList as $semantics) {
            $insertData[] = [$suiteId, $corpId, $semantics];
        }
        return \Yii::$app->db->createCommand()->batchInsert(SuiteCorpSemantics::tableName(), SuiteCorpSemantics::CHANGE_FIELDS, $insertData)->execute();
    }
}
