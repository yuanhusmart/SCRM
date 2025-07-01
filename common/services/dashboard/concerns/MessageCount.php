<?php

namespace common\services\dashboard\concerns;

use Aliyun\OTS\Consts\AggregationTypeConst;
use Aliyun\OTS\Consts\GroupByTypeConst;
use Aliyun\OTS\Consts\QueryTypeConst;
use Carbon\Carbon;
use common\helpers\Data;
use common\helpers\Maker;
use common\sdk\TableStoreChain;

/**
 * @method Carbon|self start($value = null)
 * @method Carbon|self end($value = null)
 * @method string|self corpId($value = null)
 * @method string|self suiteId($value = null)
 */
class MessageCount
{
    use Maker, Data;

    public $start;

    public $end;

    public $corpId;

    public $suiteId;

    public function execute()
    {
        $chain   = new TableStoreChain();
        $client  = $chain->OtsCreateClient();

        $request = [
            'table_name'   => TableStoreChain::DEFAULT_TABLE_NAME,
            'index_name'   => TableStoreChain::DEFAULT_TABLE_INDEX_NAME,
            'search_query' => [
                'offset'    => 0,
                'limit'     => 0,
                'get_total_count' => true,
                'query'     => [
                    'query_type' => QueryTypeConst::BOOL_QUERY,
                    'query'      => [
                        'must_queries' => [
                            [
                                'query_type' => QueryTypeConst::TERM_QUERY,
                                'query'      => [
                                    'field_name' => 'corp_id',
                                    'term'       => $this->corpId
                                ]
                            ],
                            [
                                'query_type' => QueryTypeConst::TERM_QUERY,
                                'query'      => [
                                    'field_name' => 'suite_id',
                                    'term'       => $this->suiteId
                                ]
                            ],
                            [
                                'query_type' => QueryTypeConst::RANGE_QUERY,
                                'query'      => [
                                    'field_name'    => 'send_time',
                                    'range_from'    => $this->start->startOfDay()->getTimestamp(),
                                    'range_to'      => $this->end->endOfDay()->getTimestamp(),
                                    'include_lower' => false,
                                    'include_upper' => false
                                ]
                            ]
                        ]
                    ]
                ],
                'group_bys' => [
                    'group_bys' => [
                        // 发送消息条数
                        [
                            'name' => 'group_by_sender_id',
                            'type' => GroupByTypeConst::GROUP_BY_FIELD,
                            'body' => [
                                'field_name'    => 'sender_id',
                                'size'          => 2000,
                                'min_doc_count' => 0,
                                'sub_aggs'      => [
                                    [
                                        'name' => 'total_message',
                                        'type' => AggregationTypeConst::AGG_SUM,
                                        'body' => [
                                            'field_name' => 'msgid',
                                            'missing'    => 0,
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        // 接收消息条数
                        [
                            'name' => 'group_by_receiver',
                            'type' => GroupByTypeConst::GROUP_BY_FIELD,
                            'body' => [
                                'field_name'    => 'receiver_list.id',
                                'size'          => 2000,
                                'min_doc_count' => 0,
                                'sub_aggs'      => [
                                    [
                                        'name' => 'total_message',
                                        'type' => AggregationTypeConst::AGG_SUM,
                                        'body' => [
                                            'field_name' => 'msgid',
                                            'missing'    => 0,
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $client->search($request);

        // 根据结果, 整理出人员对应的条数
        $result = $response['group_bys']['group_by_results'];

        return [
            'sender' => collect($result)->where('name', 'group_by_sender_id')->first()['group_by_result']['items'] ?? [],
            'receiver' => collect($result)->where('name', 'group_by_receiver')->first()['group_by_result']['items'] ?? []
        ];
    }
}