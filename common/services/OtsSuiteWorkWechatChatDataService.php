<?php

namespace common\services;


use common\errors\Code;
use common\errors\ErrException;
use common\models\OtsSuiteWorkWechatChatData;
use common\models\SuiteCorpSessionsMember;
use common\sdk\TableStoreChain;

/**
 * Class OtsSuiteWorkWechatChatDataService
 * @package common\services
 */
class OtsSuiteWorkWechatChatDataService extends Service
{
    // 企业微信 会话内容 广播 交换机
    const MQ_FAN_OUT_EXCHANGE_CHAT_MSG = 'aaw.fan.out.chat.msg.dir.ex';
    // 企业微信 会话内容 广播 队列（处理消息存储）
    const MQ_FAN_OUT_QUEUE_CHAT_MSG_DATA = 'aaw.fan.out.chat.msg.data.que';
    // 企业微信 会话内容 广播 队列（处理最近聊天会话）
    const MQ_FAN_OUT_QUEUE_CHAT_MSG_SESSIONS = 'aaw.fan.out.chat.msg.sessions.que';
    // 企业微信 会话内容 广播 队列 （处理企业用户会话轨迹）
    const MQ_FAN_OUT_QUEUE_CHAT_MSG_SESSIONS_TRACE = 'aaw.fan.out.chat.msg.sessions.trace.que';
    // 企业微信 会话内容 广播 队列 （处理企业用户会话同意情况）
    const MQ_FAN_OUT_QUEUE_CHAT_MSG_AGREE = 'aaw.fan.out.chat.msg.agree.que';
    // 企业微信 会话内容 广播 队列（处理外部联系人ID转换）
    const MQ_FAN_OUT_QUEUE_CHAT_MSG_EXTERNAL_CONVERT = 'aaw.fan.out.chat.msg.external.convert.que';
    // 企业微信 会话内容 广播 队列（处理内部群组数据）
    const MQ_FAN_OUT_QUEUE_CHAT_INTERNAL_GROUP_SESSIONS = 'aaw.fan.out.chat.msg.internal.group.que';
    // 企业微信 会话内容 广播 routingKey
    const MQ_FAN_OUT_ROUTING_KEY_CHAT_MSG = 'aaw.fan.out.chat.msg.rk';


    // 企业微信 命中关键词规则的会话内容 广播 交换机
    const MQ_FAN_OUT_EXCHANGE_CHAT_HIT_MSG = 'aaw.fan.out.chat.hit.msg.dir.ex';
    // 企业微信 命中关键词规则的会话内容 广播 routingKey
    const MQ_FAN_OUT_ROUTING_KEY_CHAT_HIT_MSG = 'aaw.fan.out.chat.hit.msg.rk';
    // 企业微信 命中关键词规则的会话内容 广播 队列（处理消息存储）
    const MQ_FAN_OUT_QUEUE_CHAT_HIT_MSG = 'aaw.fan.out.chat.hit.msg.que';


    /**
     * @param $params
     * @return array
     * @throws ErrException
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public static function items($params)
    {
        list($page, $perPage) = self::getPageInfo($params);
        $sort = self::getString($params, 'sort', TableStoreChain::SORT_DESC);
        // 创建TableStoreChain实例
        $tableStore = new TableStoreChain(
            OtsSuiteWorkWechatChatData::TABLE_NAME,
            OtsSuiteWorkWechatChatData::TABLE_INDEX_NAME
        );
        // 设置分页
        $tableStore->page($page, $perPage);
        // 设置返回字段
        if ($returnNames = self::getArray($params, 'return_names')) {
            $tableStore->select($returnNames);
        }
        // 添加排序 send_time 倒序
        $tableStore->orderBy('send_time', $sort);
        // 翻页 $nextToken 需要base64解码
        if ($nextToken = self::getString($params, 'next_token')) {
            $tableStore->token($nextToken);
        }
        // 添加查询条件
        $tableStore = self::buildTableStoreChainWhere($tableStore, $params);
        // 执行查询
        return $tableStore->get();
    }

    /**
     * @param $params
     * @return array
     * @throws ErrException
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public static function statistics($params)
    {
        // 创建TableStoreChain实例
        $tableStore = new TableStoreChain(
            OtsSuiteWorkWechatChatData::TABLE_NAME,
            OtsSuiteWorkWechatChatData::TABLE_INDEX_NAME
        );

        // 设置偏移量和限制
        $tableStore->offsetLimit(0, 0);

        // 添加分组查询
        $tableStore->groupByField('sender_type_count', 'sender_type', 3, [], [], 0);

        // 设置返回类型为NONE
        $tableStore->select([]);

        // 添加查询条件
        $tableStore = self::buildTableStoreChainWhere($tableStore, $params);

        // 执行查询并返回结果
        return $tableStore->get();
    }

    /**
     * 使用TableStoreChain构建查询条件
     * @param TableStoreChain $tableStore
     * @param $params
     * @return TableStoreChain
     * @throws ErrException
     */
    public static function buildTableStoreChainWhere(TableStoreChain $tableStore, $params)
    {
        $suiteId = self::getString($params, 'suite_id');
        if (!$suiteId) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        $tableStore->whereTerm('suite_id', $suiteId);

        // 企业ID
        if ($corpId = self::getString($params, 'corp_id')) {
            $tableStore->whereTerm('corp_id', $corpId);
        }

        // 会话ID
        if ($sessionId = self::getString($params, 'session_id')) {
            $tableStore->whereTerm('session_id', $sessionId);
        }

        // 群ID
        if ($chatid = self::getString($params, 'chatid')) {
            $tableStore->whereTerm('chatid', $chatid);
        }

        // 消息ID
        if ($msgid = self::getArray($params, 'msgid')) {
            $tableStore->whereTerms('msgid', $msgid);
        }

        // 消息发送人
        if ($senderIds = self::getArray($params, 'sender_id')) {
            $tableStore->whereTerms('sender_id', $senderIds);
        }

        // 消息类型
        if (isset($params['msgtype']) && $params['msgtype'] !== '') {
            $tableStore->whereTerm('msgtype', intval($params['msgtype']));
        }

        // 发送时间
        $sendTimeStart = self::getInt($params, "send_time_start") ?? 0;
        $sendTimeEnd   = self::getInt($params, "send_time_end") ?? time();
        if ($sendTimeStart || $sendTimeEnd) {
            $tableStore->whereRange('send_time', $sendTimeStart, $sendTimeEnd, true, true);
        }

        return $tableStore;
    }

    /**
     * @param $params
     * @return array|mixed
     * @throws ErrException
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public static function getMsgById($params)
    {
        if (!$msgId = self::getArray($params, 'msgid')) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        // 创建TableStoreChain实例
        $tableStore = new TableStoreChain(
            OtsSuiteWorkWechatChatData::TABLE_NAME,
            OtsSuiteWorkWechatChatData::TABLE_INDEX_NAME
        );

        // 设置查询条件
        $tableStore->whereTerms('msgid', $msgId);

        // 设置返回字段
        if ($returnNames = self::getArray($params, 'return_names')) {
            $tableStore->select($returnNames);
        }

        // 执行查询
        $resp = $tableStore->get();

        // 处理结果
        $data = [];
        if (!empty($resp['rows'])) {
            $data = $resp['rows'];
        }

        return $data;
    }

    /**
     * @param $params
     * @return mixed|null
     * @throws ErrException
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public static function statisticsBySession($params)
    {
        $suiteId   = self::getString($params, 'suite_id');
        $corpId    = self::getString($params, 'corp_id');
        $sessionId = self::getString($params, 'session_id');

        if (!$suiteId || !$corpId || !$sessionId) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        // 创建TableStoreChain实例
        $tableStore         = new TableStoreChain(
            OtsSuiteWorkWechatChatData::TABLE_NAME,
            OtsSuiteWorkWechatChatData::TABLE_INDEX_NAME
        );
        $data['statistics'] = $tableStore->whereTerm('suite_id', $suiteId)
                                         ->whereTerm('corp_id', $corpId)
                                         ->whereTerm('session_id', $sessionId)
                                         ->offsetLimit(0, 0)
                                         ->count('session_msg_count', 'msgid')
                                         ->distinctCount('session_send_date_count', 'send_date')
                                         ->select([])
                                         ->getAggResult();

        $data['session_member'] = SuiteCorpSessionsMember::find()
                                                         ->where([
                                                             'suite_id'   => $suiteId,
                                                             'corp_id'    => $corpId,
                                                             'session_id' => $sessionId
                                                         ])
                                                         ->asArray()
                                                         ->with([
                                                             'externalContactByUserid',
                                                             'accountByUserid.accountsDepartmentByAccount.departmentByAccountsDepartment'
                                                         ])
                                                         ->all();
        return $data;
    }

}