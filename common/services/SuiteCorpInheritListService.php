<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\Account;
use common\models\SuiteCorpInherit;
use common\models\SuiteCorpInheritList;
use common\models\SuiteCorpSessions;

/**
 * Class SuiteCorpInheritListService
 * @package common\services
 */
class SuiteCorpInheritListService extends Service
{

    /**
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     */
    public static function create($params)
    {
        $attributes = self::includeKeys($params, SuiteCorpInheritList::CHANGE_FIELDS);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $create = new SuiteCorpInheritList();
        $create->load($attributes, '');
        //校验参数
        if (!$create->validate()) {
            throw new ErrException(Code::PARAMS_ERROR, $create->getErrors());
        }
        if (!$create->save()) {
            throw new ErrException(Code::CREATE_ERROR, $create->getErrors());
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
        $data = SuiteCorpInheritList::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        $attributes = self::includeKeys($params, SuiteCorpInheritList::CHANGE_FIELDS);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data->attributes = $attributes;
        if (!$data->save()) {
            throw new ErrException(Code::UPDATE_ERROR, $data->getErrors());
        }
        return true;
    }

    /**
     * @param $inheritId
     * @param $items
     * @return int
     * @throws \yii\db\Exception
     */
    public static function batchInsertInheritList($inheritId, $items)
    {
        $time       = time();
        $insertData = [];
        foreach ($items as $item) {
            $insertData[] = [
                $inheritId,
                $item['userid'],
                $item['heir'],
                $item['type'],
                $item['status'] ?? SuiteCorpInheritList::STATUS_2,
                $item['external_name'],
                $item['external_id'],
                $item['takeover_time'] ?? 0,
                $item['errmsg'] ?? '',
                $item['created_at'] ?? $time,
                $item['updated_at'] ?? $time
            ];
        }
        return \Yii::$app->db->createCommand()->batchInsert(SuiteCorpInheritList::tableName(), SuiteCorpInheritList::CHANGE_FIELDS, $insertData)->execute();
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
        $inheritId = self::getInt($params, 'inherit_id');
        if (!$inheritId) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $query = SuiteCorpInheritList::find()->andWhere(["inherit_id" => $inheritId]);
        // 接替状态， 1-接替完毕 2-等待接替 3-客户拒绝 4-接替成员客户达到上限 9-失败
        if ($status = self::getInt($params, 'status')) {
            $query->andWhere(["status" => $status]);
        }
        $total  = $query->count();
        $offset = ($page - 1) * $per_page;
        $resp   = [];
        if ($total > 0) {
            $resp = $query->orderBy(['id' => SORT_DESC])->offset($offset)->limit($per_page)->asArray()->all();
        }
        return [
            'CorpInheritList' => $resp,
            'pagination'      => [
                'page'     => $page,
                'per_page' => $per_page,
                'total'    => intval($total)
            ]
        ];
    }

    /**
     * @param $id
     * @param $type
     * @return array
     */
    public static function getExternalIdByType($id, $type): array
    {
        $externalIds = SuiteCorpInheritList::find()
                                           ->andWhere(['inherit_id' => $id])
                                           ->andWhere(['type' => $type])
                                           ->select('external_id')
                                           ->asArray()
                                           ->all();
        return empty($externalIds) ? [] : array_column($externalIds, 'external_id');
    }

    /**
     * @param $params
     * @return array
     * @throws ErrException
     */
    public static function itemsByExternal($params)
    {
        $suiteId    = self::getString($params, 'suite_id');
        $corpId     = self::getString($params, 'corp_id');
        $externalId = self::getString($params, 'external_id');
        $heir       = self::getString($params, 'heir');
        if (!$suiteId || !$corpId || !$externalId || !$heir) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $resp            = SuiteCorpInheritList::find()
                                               ->select('id,userid,heir,external_id,takeover_time')
                                               ->andWhere(["type" => SuiteCorpInheritList::TYPE_1])
                                               ->andWhere(["status" => SuiteCorpInheritList::STATUS_1])
                                               ->andWhere(["external_id" => $externalId])
                                               ->andWhere(['Exists',
                                                   SuiteCorpInherit::find()
                                                                   ->select(SuiteCorpInherit::tableName() . '.id')
                                                                   ->andWhere(SuiteCorpInherit::tableName() . ".id=" . SuiteCorpInheritList::tableName() . ".inherit_id")
                                                                   ->andWhere([SuiteCorpInherit::tableName() . ".suite_id" => $suiteId])
                                                                   ->andWhere([SuiteCorpInherit::tableName() . ".corp_id" => $corpId])
                                                                   ->andWhere([SuiteCorpInherit::tableName() . ".status" => SuiteCorpInherit::INHERIT_STATUS_3])
                                               ])
                                               ->orderBy(['takeover_time' => SORT_DESC, 'id' => SORT_DESC])
                                               ->asArray()
                                               ->all();
        $heirInheritList = [];
        foreach ($resp as $item) {
            if ($item['heir'] == $heir) {               // 如果 接收人 = 存在 并且匹配 进入以下逻辑
                $heirInheritList[] = $item;             // 接收人 进入带查询列表
                $heir              = $item['userid'];   // 初始化接收人 为 交接人 继续向下匹配
            }
        }
        $return = [];
        foreach ($heirInheritList as $inheritItem) {
            $account = Account::find()
                              ->andWhere(['suite_id' => $suiteId, 'corp_id' => $corpId])
                              ->andWhere(['in', 'userid', [$inheritItem['userid'], $inheritItem['heir']]])
                              ->select('userid,jnumber,nickname')
                              ->indexBy('userid')
                              ->asArray()
                              ->all();

            $inheritItem['userid_info'] = $account[$inheritItem['userid']] ?? [];
            $inheritItem['heir_info']   = $account[$inheritItem['heir']] ?? [];

            $inheritItem['sessions_max_last_at'] = SuiteCorpSessions::find()
                                                                    ->andWhere([
                                                                        'suite_id'           => $suiteId,
                                                                        'corp_id'            => $corpId,
                                                                        'kind'               => SuiteCorpSessions::KIND_1,
                                                                        'userid'             => $inheritItem['userid'],
                                                                        'sessions_chat_id'   => $inheritItem['external_id'],
                                                                        'sessions_chat_type' => SuiteCorpSessions::INSIDE_OR_OUTSIDE_2
                                                                    ])
                                                                    ->max('last_at') ?? 0;
            array_unshift($return, $inheritItem);
        }
        return $return;
    }

}
