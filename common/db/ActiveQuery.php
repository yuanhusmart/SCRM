<?php

namespace common\db;

use common\db\concerns\Paginator;
use common\models\concerns\filters\Filter;
use yii\db\Query;

class ActiveQuery extends \yii\db\ActiveQuery
{

    /**
     * 分页
     * @return Paginator
     */
    public function paginate($perPage = 15, $page = null)
    {
        $perPage = input('per_page', $perPage);
        $page    = $page ?? input('page', 1);

        $count = $this->count();
        $items = $this->offset(($page - 1) * $perPage)->limit($perPage)->all();

        return new Paginator($items, $count, $perPage, $page);
    }

    /**
     * @param Filter $filter
     * @return ActiveQuery
     */
    public function filter(Filter $filter)
    {
        return $filter->apply($this);
    }

    /**
     * @param $value
     * @param $callback
     * @param $default
     * @return $this|ActiveQuery|mixed
     */
    public function when($value, $callback, $default = null)
    {
        if ($value) {
            return $callback($this, $value) ?: $this;
        } elseif ($default) {
            return $default($this, $value) ?: $this;
        }

        return $this;
    }

    /**
     * 获取表别名
     * @return mixed|string|void
     */
    public function getAlias()
    {
        if (empty($this->from)) {
            // 默认使用模型表名（不带别名）
            $modelClass = $this->modelClass;
            return $modelClass::tableName();
        }
        // 解析 from 中的别名（兼容直接写表名或设置别名）
        foreach ($this->from as $alias => $table) {
            if (is_string($alias)) {
                return $alias; // 返回用户设置的别名（例如 alias('t')）
            } else {
                // 直接写表名时，返回表名作为别名（例如 from('user')）
                return $table;
            }
        }
    }

    /**
     * 数据权限查询
     * @param string $idAs 别名:model.id
     * @return $this
     */
    public function dataPermission(string $idAs): ActiveQuery
    {
        $this->andWhere([
            'exists',
            (new Query())
                ->select(['id'])
                ->from(['permission_accounts' => auth()->getDataPermissionAccountIdBySystem()])
                ->where(sprintf('permission_accounts.id = %s',$idAs))
        ]);
        return $this;
    }

    /**
     * 查询权限控制
     * 代替 dataPermission
     * @param string|array $column 字段
     */
    public function accessControl($column, $type = 'account_id', $condition = 'OR')
    {
        if(auth()->isCorpAdmin()) return $this;

        return $this->andWhere(value(function () use($column, $type, $condition){
            $data = [];
            $data[] = $condition;

            $ids = auth()->getStaffUnderling($type);

            foreach((array)$column as $item){
                $data[] = [$item => $ids];
            }

            return $data;
        }));
    }


    /**
     * 员工关键字查询
     * @param string $keyword 关键字
     * @return $this
     * @throws \common\errors\ErrException
     */
    public function accountKeyword(string $keyword): ActiveQuery
    {
        $this->when($keyword,function ($query, $keyword){
            if (is_numeric($keyword)){
                $this->andWhere([\common\models\Account::asField('mobile') => $keyword]);
            }else{
                $suiteId = auth()->suiteId();
                $corpId = auth()->corpId();
                //通过姓名查询接口
                $api = \common\services\SuiteService::contactSearch([
                    //查询的企业corpid
                    'auth_corpid' => $corpId,
                    //搜索关键词。当查询用户时应为用户名称、名称拼音或者英文名；当查询部门时应为部门名称或者部门名称拼音
                    'query_word' => $keyword,
                    //查询类型 1：查询用户，返回用户userid列表 2：查询部门，返回部门id列表。 不填该字段或者填0代表同时查询部门跟用户
                    'query_type' => 1,
                    //查询范围，仅查询类型包含用户时有效。 0：只查询在职用户 1：同时查询在职和离职用户（离职用户仅当离职前有激活企业微信才可以被搜到）
                    //'query_range' => 1,
                    //查询返回的最大数量，默认为50，最多为200，查询返回的数量可能小于limit指定的值。limit会分别控制在职数据和离职数据的数量。
                    'limit' => 200,
                ]);
                $userids = $api['query_result']['user']['userid'] ?? [];
                $accountIds = \common\models\Account::find()
                    ->select(['id'])
                    ->where([
                        'suite_id' => $suiteId,
                        'corp_id' => $corpId,
                        'userid' => $userids,
                    ])
                    ->column();
                $this->andWhere([\common\models\Account::asField('id') => $accountIds]);
            }
        });
        return $this;
    }

    /**
     * 关键字查询
     * @param string $keyword 关键词
     * @param string $numberField 数字类型查询字段
     * @param string $stringField 字符串类型查询字段
     * @return $this
     */
    public function keyword(string $keyword,string $numberField, string $stringField): ActiveQuery
    {
        $this->when($keyword,function ($query, $keyword) use($numberField,$stringField){
            if (is_numeric($keyword)){
                $this->andWhere([$numberField => $keyword]);
            }else{
                $this->andWhere(['LIKE',$stringField,$keyword]);
            }
        });
        return $this;
    }

    /**
     * 时间范围查询(>=)
     * @param int|null $at 入参时间戳
     * @param string $atField 时间字段
     * @return $this|ActiveQuery
     */
    public function rangeGte(?int $at, string $atField): ActiveQuery
    {
        return $this->rangeAt($at, $atField, '>=');
    }

    /**
     * 时间范围查询(<=)
     * @param int|null $at 入参时间戳
     * @param string $atField 时间字段
     * @return $this|ActiveQuery
     */
    public function rangeLte(?int $at, string $atField): ActiveQuery
    {
        return $this->rangeAt($at, $atField, '<=');
    }

    /**
     * 时间范围查询
     * @param int|null $at 入参时间戳
     * @param string $atField 时间字段
     * @param string $op 表达式
     * @return $this
     */
    public function rangeAt(?int $at, string $atField, string $op): ActiveQuery
    {
        $this->when((!is_null($at) && $at > 0),function ($query) use($at, $atField,$op){
            $this->andWhere([$op, $atField, $at]);
        });
        return $this;
    }

    /**
     * 自定义分页处理
     * @param array $params 请求参数
     * @param callable|null $callback 手动依次处理数据的回调函数
     * @return array|null
     */
    public function myPage(array $params,callable $callback = null)
    {
        return \common\helpers\Utils::pagination($this, $params, $this->modelClass, 'items', $callback);
    }

}
