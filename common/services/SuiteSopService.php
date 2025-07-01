<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteSop;
use common\models\SuiteSopItem;
use common\models\SuiteSopVersion;
use yii\db\Exception;

/**
 * 行业适配-SOP阶段配置
 */
class SuiteSopService extends Service
{
    /**
     * 全局复用
     * @param array $params
     * @param null $industry
     * @return void
     * @throws ErrException
     * @throws Exception
     */
    public static function reuse(array $params, $industry = null)
    {
        !$industry && $industry = SuiteCorpIndustryService::getOne($params);
        /** @var SuiteSop $sop */
        $sop = SuiteSop::corp()->andWhere(['industry_no' => $industry->industry_no])->one();
        $targetIndustryNo = self::getString($params, 'target_industry_no');
        if ($targetIndustryNo){
            $other = SuiteSop::corp()->andWhere(['=', 'industry_no', $targetIndustryNo])->all();
        }else{
            $other = SuiteSop::corp()->andWhere(['!=', 'industry_no', $industry->industry_no])->all();
        }
        if ($other){
            $otherSopNo = collect($other)->pluck('sop_no')->all();
            SuiteSop::updateAllCounters(['version' => 1], ['IN', 'sop_no', $otherSopNo]);

            $items = SuiteSopItem::find()->where(['sop_no' => $sop->sop_no])->asArray()->all();
            $insItems = [];
            foreach ($otherSopNo as $otherSopNoItem){
                collect($items)->map(function ($item) use($otherSopNoItem, &$insItems){
                    $insItems[] = [
                        'sop_no' => $otherSopNoItem,
                        'name' => $item['name'],
                        'description' => $item['description'],
                        'indicator' => $item['indicator'],
                        'todo_item' => json_encode(json_decode($item['todo_item'],true), JSON_UNESCAPED_UNICODE),
                        'sort' => $item['sort'],
                        'type' => $item['type'],
                    ];
                });
            }
            SuiteSopItem::deleteAll(['IN', 'sop_no', $otherSopNo]);
            $insItems && SuiteSopItem::batchInsert($insItems);

            if ($targetIndustryNo){
                $other = SuiteSop::corp()->andWhere(['=', 'industry_no', $targetIndustryNo])->all();
            }else{
                $other = SuiteSop::corp()->andWhere(['!=', 'industry_no', $industry->industry_no])->all();
            }
            $insVersions = [];
            foreach ($otherSopNo as $otherSopNoItem){
                /** @var SuiteSop $insVersionSop */
                $insVersionSop = collect($other)->where('sop_no',$otherSopNoItem)->first();
                $insVersionSop->items;
                $insVersions[] = [
                    'sop_no' => $otherSopNoItem,
                    'version' => $insVersionSop->version,
                    'content' => json_encode($insVersionSop->toArray(),JSON_UNESCAPED_UNICODE),
                    'created_at' => time(),
                    'updated_at' => time(),
                ];
            }
            $insVersions && SuiteSopVersion::batchInsert($insVersions);
        }
    }

    /**
     * 新增/修改行业SOP阶段
     * @param array $params
     * @return void
     * @throws ErrException
     * @throws Exception
     */
    public static function save(array $params)
    {
        $items = self::getArray($params,'items');
        $industryNo = self::getString($params,'industry_no');
        if (!$industryNo || !$items) {
            throw new ErrException(Code::PARAMS_ERROR, '缺少必须参数');
        }
        if (count($items) > 8){
            throw new ErrException(Code::PARAMS_ERROR, '阶段不能超过8个');
        }
        /** @var SuiteSop $sop */
        $sop = SuiteSop::corp()->andWhere(['industry_no' => $industryNo,])->one();
        if ($sop) {
            //修改
            $sop->version += 1;
            $sop->updated_at = time();
        } else {
            //新增
            $sop = new SuiteSop();
            $sop->bindCorp();
            $sop->sop_no = snowflakeId();
            $sop->industry_no = $industryNo;
            $sop->version = 1;
            $sop->created_at = time();
            $sop->updated_at = time();
        }

        $newItems = [];
        foreach ($items as $item) {
            if (!isset($item['name'])) {
                throw new ErrException(Code::PARAMS_ERROR, '阶段配置缺少阶段名称');
            }
            if (!isset($item['description'])) {
                throw new ErrException(Code::PARAMS_ERROR, '阶段配置缺少描述');
            }
            if (!isset($item['indicator'])) {
                throw new ErrException(Code::PARAMS_ERROR, '阶段配置缺少阶段指标');
            }
            if (!isset($item['sort'])) {
                throw new ErrException(Code::PARAMS_ERROR, '阶段配置缺少排序');
            }
            if (!isset($item['type'])) {
                throw new ErrException(Code::PARAMS_ERROR, '阶段配置缺少阶段类型');
            }
            if (!in_array($item['type'], [1, 2])) {
                throw new ErrException(Code::PARAMS_ERROR, '阶段配置中的阶段类型错误');
            }
            if (!isset($item['todo_item']) || !is_array($item['todo_item'])) {
                throw new ErrException(Code::PARAMS_ERROR, '阶段配置缺少待办项');
            }
            $todo_items = $item['todo_item'];
            $todo_items_count = count($todo_items);
            if ($todo_items_count > 5) {
                throw new ErrException(Code::PARAMS_ERROR, '阶段配置待办项不能超过5个');
            }
            $todo_items_len = 0;
            //一个变量支持128个utf8字符,每个事项会用;拼接，所以在下面循环计算字符数每次要加上
            foreach ($todo_items as $todo_item){
                $todo_items_len += mb_strlen($todo_item, 'utf8');
            }
            $max_len = 128 - $todo_items_count;
            if ($todo_items_len > $max_len) {
                throw new ErrException(Code::PARAMS_ERROR, sprintf('阶段配置待办项总字符数不能超过%s个字符',$max_len));
            }

            $newItems[] = [
                'sop_no' => $sop->sop_no,
                'name' => $item['name'],
                'description' => $item['description'],
                'indicator' => $item['indicator'],
                'todo_item' => json_encode($todo_items, JSON_UNESCAPED_UNICODE),
                'sort' => $item['sort'],
                'type' => $item['type'],
            ];
        }
        $sop->save();
        SuiteSopItem::deleteAll(['sop_no' => $sop->sop_no]);
        $newItems && SuiteSopItem::batchInsert($newItems);
        $sop->createVersion();
    }

}
