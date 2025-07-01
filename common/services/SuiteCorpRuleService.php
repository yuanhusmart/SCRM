<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpConfig;
use common\models\SuiteCorpKeyword;
use common\models\SuiteCorpRule;
use common\models\SuiteCorpSemantics;

/**
 * Class SuiteCorpRuleService
 * @package common\services
 */
class SuiteCorpRuleService extends Service
{

    /**
     * @param $params
     * @return true
     * @throws ErrException
     * @throws \yii\db\Exception
     */
    public static function create($params)
    {
        if (empty($params['suite_id']) || empty($params['corp_id'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $config = SuiteCorpConfig::findOne(['suite_id' => $params['suite_id'], 'corp_id' => $params['corp_id']]);
        if (empty($config)) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        // 规则名称 写死
        $params['name'] = '风险行为配置';
        $programParams  = ['name' => $params['name']];
        if (!empty($params['open_keyword']) && $params['open_keyword'] == SuiteCorpRule::OPEN_KEYWORD_1) {
            $wordList = SuiteCorpKeyword::find()->where(['suite_id' => $config->suite_id, 'corp_id' => $config->corp_id])->select('word')->asArray()->column();
            if (count($wordList) > 20) {
                throw new ErrException(Code::PARAMS_ERROR, '关键词不能超过20个');
            }
            if ($wordList) {
                $programParams['keyword'] = [
                    'word_list'         => $wordList,
                    'is_case_sensitive' => isset($params['is_case_sensitive']) ? (int) $params['is_case_sensitive'] : SuiteCorpRule::IS_CASE_SENSITIVE_1,
                ];
            }
        }

        if (!empty($params['semantics_list'])) {
            foreach ($params['semantics_list'] as $item) {
                $programParams['semantics']['semantics_list'][] = intval($item);
            }

        }

        if (empty($programParams['keyword']) && empty($programParams['semantics'])) {
            throw new ErrException(Code::PARAMS_ERROR, '关键词列表与关键行为列表必须选填一项');
        }
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $create = SuiteCorpRule::find()->where(['suite_id' => $config->suite_id, 'corp_id' => $config->corp_id])->one();
            if ($create) {
                $programParams['rule_id'] = $create->rule_id;
                SuiteProgramService::executionSyncCallProgram($config->suite_id, $config->corp_id, SuiteProgramService::PROGRAM_ABILITY_UPDATE_RULE, $programParams);
            } else {
                $responseData = SuiteProgramService::executionSyncCallProgram($config->suite_id, $config->corp_id, SuiteProgramService::PROGRAM_ABILITY_CREATE_RULE, $programParams);
                // 获取企业授权给应用的知识集列表
                if (empty($responseData['rule_id'])) {
                    throw new ErrException(Code::CALL_EXCEPTION, '企业微信专区调用失败');
                }
                $params['rule_id'] = $responseData['rule_id'];
                $create            = new SuiteCorpRule();
            }
            $attributes = self::includeKeys($params, SuiteCorpRule::CHANGE_FIELDS);
            $create->load($attributes, '');
            //校验参数
            if (!$create->validate()) {
                throw new ErrException(Code::PARAMS_ERROR, $create->getErrors());
            }
            if (!$create->save()) {
                throw new ErrException(Code::CREATE_ERROR, $create->getErrors());
            }
            SuiteCorpSemantics::deleteAll(['AND', ['=', 'suite_id', $config->suite_id], ['=', 'corp_id', $config->corp_id]]);
            if (!empty($params['semantics_list'])) {
                SuiteCorpSemanticsService::batchInsertSemantics($config->suite_id, $config->corp_id, $params['semantics_list']);
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
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
        if (!$id) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $data = SuiteCorpRule::findOne($id);
            if (!$data) {
                throw new ErrException(Code::NOT_EXIST);
            }
            SuiteProgramService::executionSyncCallProgram($data->suite_id, $data->corp_id, SuiteProgramService::PROGRAM_ABILITY_DELETE_RULE, ['rule_id' => $data->rule_id]);
            if (!$data->delete()) {
                throw new ErrException(Code::DELETE_ERROR, $data->getErrors());
            }
            SuiteCorpSemantics::deleteAll(['AND', ['=', 'suite_id', $data->suite_id], ['=', 'corp_id', $data->corp_id]]);
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        return true;
    }

    /**
     * @param $params
     * @return array
     * @throws ErrException
     */
    public static function details($params)
    {
        $suiteId = self::getString($params, 'suite_id');
        $corpId  = self::getString($params, 'corp_id');

        if (!$suiteId || !$corpId) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        $query = SuiteCorpRule::find()
                              ->andWhere(["suite_id" => $suiteId])
                              ->andWhere(["corp_id" => $corpId]);

        return $query->with(['keywordByCorp', 'semanticsByCorp'])->asArray()->one();
    }

    /**
     * @param $suiteId
     * @param $corpId
     * @return true
     * @throws ErrException
     */
    public static function syncRuleByChangeWord($suiteId, $corpId)
    {
        $rule = SuiteCorpRule::find()->where(['suite_id' => $suiteId, 'corp_id' => $corpId, 'open_keyword' => SuiteCorpRule::OPEN_KEYWORD_1])->one();
        if (!empty($rule)) {
            $programParams = ['rule_id' => $rule->rule_id, 'name' => '风险行为配置'];
            $wordList      = SuiteCorpKeyword::find()->where(['suite_id' => $suiteId, 'corp_id' => $corpId])->select('word')->asArray()->column();
            if (count($wordList) > 20) {
                throw new ErrException(Code::PARAMS_ERROR, '关键词不能超过20个');
            }
            if ($wordList) {
                $programParams['keyword'] = [
                    'word_list'         => $wordList,
                    'is_case_sensitive' => $rule->is_case_sensitive,
                ];
            }

            $semanticsList = SuiteCorpSemantics::find()->where(['suite_id' => $suiteId, 'corp_id' => $corpId])->select('semantics')->asArray()->all();
            if (!empty($semanticsList)) {
                foreach ($semanticsList as $item) {
                    $programParams['semantics']['semantics_list'][] = intval($item['semantics']);
                }
            }
            SuiteProgramService::executionSyncCallProgram($suiteId, $corpId, SuiteProgramService::PROGRAM_ABILITY_UPDATE_RULE, $programParams);
        }
        return true;
    }

}
