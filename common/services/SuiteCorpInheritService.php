<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\Account;
use common\models\SuiteCorpGroupChat;
use common\models\SuiteCorpInherit;
use common\models\SuiteCorpInheritList;

/**
 * Class SuiteCorpInheritService
 * @package common\services
 */
class SuiteCorpInheritService extends Service
{

    // 继承队列
    const MQ_INHERIT_EXCHANGE    = 'aaw.inherit.handle.dir.ex';
    const MQ_INHERIT_QUEUE       = 'aaw.inherit.handle.que';
    const MQ_INHERIT_ROUTING_KEY = 'aaw.inherit.handle.rk';

    /**
     * 验证参数
     * @param $params
     * @return array
     * @throws ErrException
     */
    public static function verifyParams($params): array
    {
        $verifyParam = self::includeKeys($params, SuiteCorpInherit::CHANGE_FIELDS);

        if (empty($verifyParam['suite_id']) || empty($verifyParam['corp_id']) || empty($verifyParam['userid'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        if (empty($verifyParam['heir'])) {
            throw new ErrException(Code::PARAMS_ERROR, '请确认接收人');
        }

        if (empty($verifyParam['type']) || !in_array($verifyParam['type'], array_keys(SuiteCorpInherit::TYPE_DESC))) {
            throw new ErrException(Code::PARAMS_ERROR, '请确认继承类型');
        }

        if (empty($verifyParam['inherit_type']) || !in_array($verifyParam['inherit_type'], array_keys(SuiteCorpInherit::INHERIT_TYPE_DESC))) {
            throw new ErrException(Code::PARAMS_ERROR, '请确认继承类型');
        }

        // 如果选择了继承客户或者继承群 则必须传入list参数，全部继承则无需list参数
        if (in_array($verifyParam['inherit_type'], [SuiteCorpInherit::INHERIT_TYPE_1, SuiteCorpInherit::INHERIT_TYPE_2]) && empty($params['list'])) {
            throw new ErrException(Code::PARAMS_ERROR, '请确认继承客户或继承群范围');
        }
        return $verifyParam;
    }

    /**
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     * @throws \yii\db\Exception
     */
    public static function create($params)
    {
        $attributes  = self::verifyParams($params);
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $count = SuiteCorpInherit::find()
                                     ->andWhere(['suite_id' => $attributes['suite_id']])
                                     ->andWhere(['corp_id' => $attributes['corp_id']])
                                     ->andWhere(['userid' => $attributes['userid']])
                                     ->andWhere(['in', 'status', [SuiteCorpInherit::INHERIT_STATUS_1, SuiteCorpInherit::INHERIT_STATUS_2]])
                                     ->count();
            if ($count > 0) {
                throw new ErrException(Code::PARAMS_ERROR, '当前用户有客户在交接，请在客户交接后，再来操作接口使用人变更');
            }
            $corpInherit = new SuiteCorpInherit();
            $corpInherit->load($attributes, '');
            // 校验参数
            if (!$corpInherit->validate()) {
                throw new ErrException(Code::PARAMS_ERROR, $corpInherit->getErrors());
            }
            if (!$corpInherit->save()) {
                throw new ErrException(Code::CREATE_ERROR, $corpInherit->getErrors());
            }
            $corpInheritId = $corpInherit->getPrimaryKey();
            // 添加 查看权限授权账号集合
            if (!empty($params['list']) && in_array($params['inherit_type'], [SuiteCorpInherit::INHERIT_TYPE_1, SuiteCorpInherit::INHERIT_TYPE_2, SuiteCorpInherit::INHERIT_TYPE_4])) {
                /* 追加群组信息 */
                if ($params['inherit_type'] == SuiteCorpInherit::INHERIT_TYPE_4) {
                    $groupChats = SuiteCorpGroupChat::find()
                                                    ->andWhere(['suite_id' => $params['suite_id'], 'corp_id' => $params['corp_id']])
                                                    ->andWhere(['in', 'owner', array_column($params['list'], 'userid')]) //交接人用户ID数组
                                                    ->andWhere(['group_type' => SuiteCorpGroupChat::GROUP_TYPE_1])
                                                    ->select('name,chat_id,owner')
                                                    ->asArray()
                                                    ->indexBy('owner')
                                                    ->all();

                    foreach ($groupChats as $value) {
                        $params['list'][] = [
                            'userid'        => $value['owner'],
                            'heir'          => $params['list'][0]['heir'],
                            'type'          => SuiteCorpInheritList::TYPE_2,
                            'external_name' => $value['name'],
                            'external_id'   => $value['chat_id'],
                        ];
                    }
                }

                SuiteCorpInheritListService::batchInsertInheritList($corpInheritId, $params['list']);
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        return $corpInheritId;
    }

    /**
     * @param $params
     * @return true
     * @throws ErrException
     * @throws \yii\db\Exception
     */
    public static function batchCreate($params)
    {
        if (empty($params['user_ids'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            foreach ($params['user_ids'] as $item) {
                $params['userid']       = $item;
                $params['type']         = SuiteCorpInherit::TYPE_2;
                $params['inherit_type'] = SuiteCorpInherit::INHERIT_TYPE_3;
                self::create($params);
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
     * @return array
     * @throws ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public static function items($params)
    {
        list($page, $per_page) = self::getPageInfo($params);
        $suiteId = self::getString($params, 'suite_id');
        if (!$suiteId) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $query = SuiteCorpInherit::find()->andWhere(["suite_id" => $suiteId]);

        // 企业
        if ($corpId = self::getString($params, 'corp_id')) {
            $query->andWhere(["corp_id" => $corpId]);
        }

        // 类型 1.在职继承 2.离职继承
        if ($type = self::getInt($params, 'type')) {
            $query->andWhere(["type" => $type]);
        }

        // 执行状态 1.待执行 2.执行中 3.执行完毕
        if ($status = self::getInt($params, 'status')) {
            $query->andWhere(["status" => $status]);
        }

        // 创建时间 - 开始
        if ($createdAtStart = self::getInt($params, 'created_at_start')) {
            $query->andWhere(['>=', 'created_at', $createdAtStart]);
        }
        // 创建时间 - 截止
        if ($createdAtEnd = self::getInt($params, 'created_at_end')) {
            $query->andWhere(['<=', 'created_at', $createdAtEnd]);
        }

        // 完成时间 - 开始
        if ($completeAtStart = self::getInt($params, 'complete_at_start')) {
            $query->andWhere(['>=', 'complete_at', $completeAtStart]);
        }
        // 完成时间 - 截止
        if ($completeAtEnd = self::getInt($params, 'complete_at_end')) {
            $query->andWhere(['<=', 'complete_at', $completeAtEnd]);
        }

        // 交接人
        if (!empty($params['user_value'])) {
            if (is_int($params['user_value'])) {
                $accountWhere = [Account::tableName() . ".jnumber" => $params['user_value']];
            }
            if (is_string($params['user_value'])) {
                $accountWhere = [Account::tableName() . ".nickname" => $params['user_value']];
            }
            if (!empty($accountWhere)) {
                $query->andWhere(['Exists',
                    Account::find()
                           ->select(Account::tableName() . '.id')
                           ->andWhere(Account::tableName() . ".suite_id=" . SuiteCorpInherit::tableName() . ".suite_id")
                           ->andWhere(Account::tableName() . ".corp_id=" . SuiteCorpInherit::tableName() . ".corp_id")
                           ->andWhere(Account::tableName() . ".userid=" . SuiteCorpInherit::tableName() . ".userid")
                           ->andWhere($accountWhere)
                ]);
            }
        }

        // 接收人
        if (!empty($params['heir_value'])) {
            if (is_int($params['heir_value'])) {
                $heirWhere = [Account::tableName() . ".jnumber" => $params['heir_value']];
            }
            if (is_string($params['heir_value'])) {
                $heirWhere = [Account::tableName() . ".nickname" => $params['heir_value']];
            }
            if (!empty($heirWhere)) {
                $query->andWhere(['Exists',
                    Account::find()
                           ->select(Account::tableName() . '.id')
                           ->andWhere(Account::tableName() . ".suite_id=" . SuiteCorpInherit::tableName() . ".suite_id")
                           ->andWhere(Account::tableName() . ".corp_id=" . SuiteCorpInherit::tableName() . ".corp_id")
                           ->andWhere(Account::tableName() . ".userid=" . SuiteCorpInherit::tableName() . ".heir")
                           ->andWhere($accountWhere)
                ]);
            }
        }

        // 创建者
        if (!empty($params['create_value'])) {
            if (is_int($params['create_value'])) {
                $query->andWhere(["create_number" => $params['create_value']]);
            }
            if (is_string($params['create_value'])) {
                $query->andWhere(["create_name" => $params['create_value']]);
            }
        }

        $total  = $query->count();
        $offset = ($page - 1) * $per_page;
        $resp   = [];
        if ($total > 0) {
            $query->with(['accountsByUserId', 'accountsByHeir', 'accountsDepartmentByUserId.departmentByAccountsDepartment', 'inheritListCountById']);
            $resp = $query->orderBy(['id' => SORT_DESC])->offset($offset)->limit($per_page)->asArray()->all();
        }
        return [
            'CorpInherit' => $resp,
            'pagination'  => [
                'page'     => $page,
                'per_page' => $per_page,
                'total'    => intval($total)
            ]
        ];
    }

}
