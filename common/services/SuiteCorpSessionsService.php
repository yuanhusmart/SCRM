<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpGroupChat;
use common\models\SuiteCorpGroupChatMember;
use common\models\SuiteCorpSessions;
use common\models\SuiteCorpSessionsMember;
use common\models\SuiteCorpSessionsTrace;

/**
 * Class SuiteCorpSessionsService
 * @package common\services
 */
class SuiteCorpSessionsService extends Service
{

    /**
     * 消息数据处理
     * @param array $params 消息参数，必须包含以下字段：
     *        - suite_id: 服务商ID
     *        - corp_id: 企业ID
     *        - session_id: 会话ID
     *        - chatid: 群组ID（单聊时为空）
     *        - send_time: 发送时间
     *        - sender: 发送者信息，包含id和type字段
     *        - receiver_list: 接收者列表，每个元素包含id和type字段
     * @return bool 处理成功返回true
     * @throws ErrException 参数错误或创建失败时抛出异常
     * @throws \yii\db\Exception 数据库事务异常
     */
    public static function msgDataHandle($params)
    {
        // 参数验证
        $requiredFields = ['suite_id', 'corp_id', 'session_id', 'send_time', 'msgid'];
        foreach ($requiredFields as $field) {
            if (empty($params[$field])) {
                throw new ErrException(Code::PARAMS_ERROR, "缺少必要参数: {$field}");
            }
        }

        // 单聊时需要验证发送者和接收者信息
        if (empty($params['chatid'])) {
            if (empty($params['sender']) || empty($params['sender']['id']) || empty($params['sender']['type'])) {
                throw new ErrException(Code::PARAMS_ERROR, '发送者信息不完整');
            }
            if (empty($params['receiver_list']) || empty($params['receiver_list'][0]) ||
                empty($params['receiver_list'][0]['id']) || empty($params['receiver_list'][0]['type'])) {
                throw new ErrException(Code::PARAMS_ERROR, '接收者信息不完整');
            }
        }

        /**
         * 会话接收逻辑 = 发送者「sender」 + 接收者「receiver_list」 均会更新最后消息时间
         * 所以【服务商企业用户会话表】中会生成 userid 分别是「发送者」和「接收者」的数据，逻辑如下
         *   1、发送者为userid，接收者为sessions_chat_id
         *   2、接收者为userid，发送者为sessions_chat_id
         *   3、群组直接存储一条消息，userid = chatid
         */
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            // 构建基础参数
            $createParams = [
                'suite_id'   => $params['suite_id'],
                'corp_id'    => $params['corp_id'],
                'session_id' => $params['session_id'],
                'chat_id'    => isset($params['chatid']) ? $params['chatid'] : '',
                'last_at'    => $params['send_time'],
                'msgid'      => $params['msgid'],
            ];

            if (empty($params['chatid'])) {
                // 单聊消息处理：需要创建两条会话成员记录（发送者和接收者）
                $createParams['kind'] = SuiteCorpSessions::KIND_1;
                // 处理发送者和接收者
                $users = [
                    ['id' => $params['receiver_list'][0]['id'], 'type' => $params['receiver_list'][0]['type']],
                    ['id' => $params['sender']['id'], 'type' => $params['sender']['type']]
                ];

                $insideOrOutside = SuiteCorpSessions::INSIDE_OR_OUTSIDE_1;

                foreach ($users as $user) {
                    if ($user['type'] == SuiteCorpSessionsMember::USER_TYPE_2) {
                        $insideOrOutside = SuiteCorpSessions::INSIDE_OR_OUTSIDE_2;
                    }
                    $memberParams              = $createParams;
                    $memberParams['userid']    = $user['id'];
                    $memberParams['user_type'] = $user['type'];
                    try {
                        SuiteCorpSessionsMemberService::create($memberParams);
                    } catch (\Exception $e) {
                        \Yii::warning('已存在，无需重复添加：' . $memberParams['session_id'] . ',' . $memberParams['userid'] . ',跳过' . $e->getMessage());
                    }
                }

                $createParams['inside_or_outside'] = $insideOrOutside;
            } else {
                // 群聊消息处理
                $createParams['kind'] = SuiteCorpSessions::KIND_2;
            }

            // 创建或更新会话记录
            self::create($createParams);

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
     */
    public static function create($params)
    {
        $attributes = self::includeKeys($params, SuiteCorpSessions::CHANGE_FIELDS);
        $sessions   = SuiteCorpSessions::findOne(['suite_id' => $attributes['suite_id'], 'corp_id' => $attributes['corp_id'], 'kind' => $attributes['kind'], 'session_id' => $attributes['session_id']]);
        if (empty($sessions)) {
            $sessions = new SuiteCorpSessions();
        } else {
            // 如果更新时间小于 表中时间 则不进行更新
            if ($params['last_at'] <= $sessions->last_at) {
                throw new ErrException(Code::PARAMS_ERROR, '无需更新');
            }
        }
        $sessions->load($attributes, '');
        // 校验参数
        if (!$sessions->validate()) {
            throw new ErrException(Code::PARAMS_ERROR, $sessions->getError());
        }
        if (!$sessions->save()) {
            throw new ErrException(Code::CREATE_ERROR, $sessions->getError());
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
        $suiteId         = self::getString($params, 'suite_id');
        $corpId          = self::getString($params, 'corp_id');
        $userid          = self::getString($params, 'userid');
        $kind            = self::getInt($params, 'kind');
        $insideOrOutside = self::getInt($params, 'inside_or_outside');

        if (!$suiteId || !$corpId || !$userid) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        if (!in_array($kind, [SuiteCorpSessions::KIND_1, SuiteCorpSessions::KIND_2])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        $query = SuiteCorpSessions::find()
                                  ->alias('s')
                                  ->andWhere(["s.suite_id" => $suiteId])
                                  ->andWhere(["s.corp_id" => $corpId])
                                  ->andWhere(["s.kind" => $kind]);

        if ($sessionId = self::getString($params, 'session_id')) {
            $query->andWhere(["s.session_id" => $sessionId]);
        }

        // 最后消息时间 - 开始
        if ($lastAtStart = self::getInt($params, 'last_at_start')) {
            $query->andWhere(['>=', 's.last_at', $lastAtStart]);
        }

        // 最后消息时间 - 截止
        if ($lastAtEnd = self::getInt($params, 'last_at_end')) {
            $query->andWhere(['<=', 's.last_at', $lastAtEnd]);
        }

        // 近日沟通时间 - 开始
        if($recentSessionsAtStart = self::getInt($params, 'recent_sessions_at_start')){
            $query->andWhere(['>=','s.last_at', $recentSessionsAtStart]);
        }
        // 近日沟通时间 - 截止
        if($recentSessionsAtEnd = self::getInt($params, 'recent_sessions_at_end')){
            $query->andWhere(['<=','s.last_at', $recentSessionsAtEnd]);
        }
  
        // 类型: 1好友, 2群聊
        if ($kind == SuiteCorpSessions::KIND_1) {
            $query->andWhere(['Exists',
                SuiteCorpSessionsMember::find()
                                       ->andWhere(SuiteCorpSessionsMember::tableName() . ".suite_id=s.suite_id")
                                       ->andWhere(SuiteCorpSessionsMember::tableName() . ".corp_id=s.corp_id")
                                       ->andWhere(SuiteCorpSessionsMember::tableName() . ".session_id=s.session_id")
                                       ->andWhere([SuiteCorpSessionsMember::tableName() . '.user_type' => SuiteCorpSessionsMember::USER_TYPE_1])
                                       ->andWhere([SuiteCorpSessionsMember::tableName() . '.userid' => $userid])
            ]);

            if ($insideOrOutside) {
                $query->andWhere(["s.inside_or_outside" => $insideOrOutside]);
                if ($insideOrOutside == SuiteCorpSessions::INSIDE_OR_OUTSIDE_1) {
                    // 内部员工
                    $query->with(['sessionsMemberById' => function ($query) {
                        $query->where(['user_type' => SuiteCorpSessionsMember::USER_TYPE_1])->with(['accountByUserid']);
                    }]);
                } elseif ($insideOrOutside == SuiteCorpSessions::INSIDE_OR_OUTSIDE_2) {
                    // 外部联系人
                    $query->with(['sessionsMemberById' => function ($query) {
                        $query->where(['user_type' => SuiteCorpSessionsMember::USER_TYPE_2])->with('externalContactByUserid');
                    }]);
                }
            }

        } else {
            $query->with(['groupChatByChatId']);
            $query->andWhere(['in', 's.session_id',
                SuiteCorpGroupChatMember::find()
                                        ->alias('gcm')
                                        ->innerJoin(SuiteCorpGroupChat::tableName() . ' AS gc', 'gcm.group_chat_id = gc.id')
                                        ->andWhere(['gc.suite_id' => $suiteId])
                                        ->andWhere(['gc.corp_id' => $corpId])
                                        ->andWhere(['gc.is_dismiss' => SuiteCorpGroupChat::IS_DISMISS_2])
                                        ->andWhere(['gcm.userid' => $userid])
                                        ->select('gc.chat_id')
                                        ->asArray()
                                        ->column()
            ]);
        }

        $total  = $query->count();
        $offset = ($page - 1) * $per_page;
        $resp   = [];
        if ($total > 0) {
            $resp = $query->orderBy(['s.last_at' => SORT_DESC])
                          ->with(['sessionsMemberById'])
                          ->offset($offset)
                          ->limit($per_page)
                          ->asArray()
                          ->all();
        }
        return [
            'Sessions'   => $resp,
            'pagination' => [
                'page'     => $page,
                'per_page' => $per_page,
                'total'    => intval($total)
            ]
        ];
    }

    /**
     * @param $params
     * @return array|mixed
     * @throws ErrException
     */
    public static function searchMsg($params)
    {
        $suiteId   = self::getString($params, 'suite_id');
        $corpId    = self::getString($params, 'corp_id');
        $sessionId = self::getString($params, 'session_id');

        if (!$suiteId || !$corpId || !$sessionId) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        $sessions = SuiteCorpSessions::find()
                                     ->andWhere(["suite_id" => $suiteId])
                                     ->andWhere(["corp_id" => $corpId])
                                     ->andWhere(["session_id" => $sessionId])
                                     ->with(['sessionsMemberById'])
                                     ->asArray()
                                     ->one();
        if (empty($sessions)) {
            throw new ErrException(Code::DATA_ERROR, '未找到会话');
        }

        if ($sessions['kind'] == SuiteCorpSessions::KIND_1) { // 单聊
            $params['chat_info']['chat_type'] = SuiteCorpSessions::KIND_1;
            $idList                           = [];
            foreach ($sessions['sessionsMemberById'] as $sessionMember) {
                if ($sessionMember['user_type'] == SuiteCorpSessionsMember::USER_TYPE_2) {
                    $idList[] = [
                        'external_userid' => $sessionMember['userid']
                    ];
                } else {
                    $idList[] = [
                        'open_userid' => $sessionMember['userid']
                    ];
                }
            }
            $params['chat_info']['id_list'] = $idList;
        } else {
            $params['chat_info']['chat_type'] = SuiteCorpSessions::KIND_2;
            $params['chat_info']['chat_id']   = $sessions['chat_id'];
        }

        $attributes = self::includeKeys($params, ['query_word', 'chat_info', 'start_time', 'end_time', 'skip_stop_words', 'limit', 'cursor']);
        return SuiteProgramService::executionSyncCallProgram($params['suite_id'], $params['corp_id'], SuiteProgramService::PROGRAM_ABILITY_SEARCH_MSG, $attributes);
    }

    /**
     * 会话名称搜索
     * @param $params
     * @return array|mixed
     * @throws ErrException
     */
    public static function searchChat($params)
    {
        $suiteId   = self::getString($params, 'suite_id');
        $corpId    = self::getString($params, 'corp_id');
        $queryWord = self::getString($params, 'query_word');
        if (!$suiteId || !$corpId || !$queryWord) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $attributes = self::includeKeys($params, ['query_word', 'limit', 'cursor']);
        return SuiteProgramService::executionSyncCallProgram($params['suite_id'], $params['corp_id'], SuiteProgramService::PROGRAM_ABILITY_SEARCH_CHAT, $attributes);
    }

    /**
     * @param $params
     * @return array
     * @throws ErrException
     */
    public static function externalContactItems($params)
    {
        list($page, $per_page) = self::getPageInfo($params);
        $suiteId        = self::getString($params, 'suite_id');
        $corpId         = self::getString($params, 'corp_id');
        $kind           = self::getInt($params, 'kind');
        $externalUserid = self::getString($params, 'external_userid');

        if (!$suiteId || !$corpId || !$externalUserid) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        if (!in_array($kind, [SuiteCorpSessions::KIND_1, SuiteCorpSessions::KIND_2])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        $query = SuiteCorpSessions::find()
            ->alias('s')
            ->andWhere(["s.suite_id" => $suiteId])
            ->andWhere(["s.corp_id" => $corpId])
            ->andWhere(["s.kind" => $kind]);


        // 最后消息时间 - 开始
        if ($lastAtStart = self::getInt($params, 'last_at_start')) {
            $query->andWhere(['>=', 's.last_at', $lastAtStart]);
        }

        // 最后消息时间 - 截止
        if ($lastAtEnd = self::getInt($params, 'last_at_end')) {
            $query->andWhere(['<=', 's.last_at', $lastAtEnd]);
        }

        // 近日沟通时间 - 开始
        if($recentSessionsAtStart = self::getInt($params, 'recent_sessions_at_start')){
            $query->andWhere(['>=','s.last_at', $recentSessionsAtStart]);
        }
        // 近日沟通时间 - 截止
        if($recentSessionsAtEnd = self::getInt($params, 'recent_sessions_at_end')){
            $query->andWhere(['<=','s.last_at', $recentSessionsAtEnd]);
        }

        if ($kind == SuiteCorpSessions::KIND_1) {
            $query->andWhere(['Exists',
                SuiteCorpSessionsMember::find()
                                       ->andWhere(SuiteCorpSessionsMember::tableName() . ".suite_id=s.suite_id")
                                       ->andWhere(SuiteCorpSessionsMember::tableName() . ".corp_id=s.corp_id")
                                       ->andWhere(SuiteCorpSessionsMember::tableName() . ".session_id=s.session_id")
                                       ->andWhere([SuiteCorpSessionsMember::tableName() . '.user_type' => SuiteCorpSessionsMember::USER_TYPE_2])
                                       ->andWhere([SuiteCorpSessionsMember::tableName() . '.userid' => $externalUserid])
            ]);

            // 内部员工
            $query->with(['sessionsMemberById' => function ($query) {
                $query->where(['user_type' => SuiteCorpSessionsMember::USER_TYPE_1])->with(['accountByUserid']);
            }]);

            // 单聊权限控制
            $query->andWhere(['Exists',
                              SuiteCorpSessionsMember::find()
                                  ->accessControl(SuiteCorpSessionsMember::asField('userid'), 'userid')
                                  ->andWhere(SuiteCorpSessionsMember::tableName() . ".suite_id=s.suite_id")
                                  ->andWhere(SuiteCorpSessionsMember::tableName() . ".corp_id=s.corp_id")
                                  ->andWhere(SuiteCorpSessionsMember::tableName() . ".session_id=s.session_id")
                                  ->andWhere([SuiteCorpSessionsMember::tableName() . '.user_type' => SuiteCorpSessionsMember::USER_TYPE_1])

            ]);

        } else {
            $query->with(['groupChatByChatId']);
            $query->andWhere(['in', 's.session_id',
                SuiteCorpGroupChatMember::find()
                                        ->alias('gcm')
                                        ->innerJoin(SuiteCorpGroupChat::tableName() . ' AS gc', 'gcm.group_chat_id = gc.id')
                                        ->andWhere(['gc.suite_id' => $suiteId])
                                        ->andWhere(['gc.corp_id' => $corpId])
                                        ->andWhere(['gc.is_dismiss' => SuiteCorpGroupChat::IS_DISMISS_2])
                                        ->andWhere(['gc.group_type' => SuiteCorpGroupChat::GROUP_TYPE_1])
                                        ->andWhere(['gcm.type' => SuiteCorpGroupChatMember::GROUP_CHAT_TYPE_2])
                                        ->andWhere(['gcm.userid' => $externalUserid])
                                        ->select('gc.chat_id')
                                        ->asArray()
                                        ->column()
            ]);

            // 群聊权限控制
            $query->andWhere(['in', 's.session_id',
                              SuiteCorpGroupChatMember::find()
                                  ->alias('gcm')
                                  ->innerJoin(SuiteCorpGroupChat::tableName() . ' AS gc', 'gcm.group_chat_id = gc.id')
                                  ->andWhere(['gc.suite_id' => $suiteId])
                                  ->andWhere(['gc.corp_id' => $corpId])
                                  ->andWhere(['gc.is_dismiss' => SuiteCorpGroupChat::IS_DISMISS_2])
                                  ->andWhere(['gc.group_type' => SuiteCorpGroupChat::GROUP_TYPE_1])
                                  ->andWhere(['gcm.type' => SuiteCorpGroupChatMember::GROUP_CHAT_TYPE_1])
                                  ->accessControl('gcm.userid', 'userid')
                                  ->select('gc.chat_id')
                                  ->asArray()
                                  ->column()
            ]);
        }

        $total  = $query->count();
        $offset = ($page - 1) * $per_page;
        $resp   = [];
        if ($total > 0) {
            $resp = $query->orderBy(['last_at' => SORT_DESC])
                          ->with(['sessionsMemberById'])
                          ->offset($offset)
                          ->limit($per_page)
                          ->asArray()
                          ->all();
        }

        return [
            'Sessions'   => $resp,
            'pagination' => [
                'page'     => $page,
                'per_page' => $per_page,
                'total'    => intval($total)
            ]
        ];
    }
}