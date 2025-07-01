<?php

namespace console\controllers;

use Aliyun\OTS\Consts\FieldTypeConst;
use Aliyun\OTS\ProtoBuffer\Protocol\FieldSchema;
use common\models\OtsSuiteWorkWechatChatData;
use common\sdk\TableStoreChain;

class OtsSuiteWorkWechatChatDataController extends BaseConsoleController
{

    /**
     * php ./yii ots-suite-work-wechat-chat-data/reindex
     * @return true
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     * @throws \common\errors\ErrException
     */
    public function actionReindex()
    {
        self::consoleLog("重新创建OTS索引开始，表名:" . OtsSuiteWorkWechatChatData::TABLE_NAME);
        $tableStore = new TableStoreChain(
            OtsSuiteWorkWechatChatData::TABLE_NAME,
            OtsSuiteWorkWechatChatData::TABLE_INDEX_NAME
        );
        $resp       = $tableStore->indexSchema(OtsSuiteWorkWechatChatData::indexSchema())
                                 ->reindex(OtsSuiteWorkWechatChatData::TABLE_INDEX_NAME);
        self::consoleLog($resp);
        self::consoleLog("重新创建OTS索引结束");
        return true;
    }

    /**
     * 重置ots表
     * php ./yii ots-suite-work-wechat-chat-data/re-table
     * @return void
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     */
    public function actionReTable()
    {
        $tableStore = new TableStoreChain(
            OtsSuiteWorkWechatChatData::TABLE_NAME,
            OtsSuiteWorkWechatChatData::TABLE_INDEX_NAME
        );

        $describeTable = OtsSuiteWorkWechatChatData::tableSchema();

        // 创建表
        $createTable = $tableStore->table($describeTable['table_meta']['table_name'])->createTable(
            $describeTable['table_meta']['primary_key_schema'],
            $describeTable['table_meta']['defined_column'],
            $describeTable['table_options']
        );
        self::consoleLog($createTable);

        $resp = $tableStore->indexSchema(OtsSuiteWorkWechatChatData::indexSchema())->reindex();

        self::consoleLog($resp);
        die;
    }

    /**
     * php ./yii ots-suite-work-wechat-chat-data/clean-data
     * @return true
     * @throws \Aliyun\OTS\OTSClientException
     * @throws \Aliyun\OTS\OTSServerException
     * @throws \common\errors\ErrException
     */
    public function actionCleanData()
    {
        // 使用TableStoreChain链式调用
        $ots = new TableStoreChain(
            OtsSuiteWorkWechatChatData::TABLE_NAME,
            OtsSuiteWorkWechatChatData::TABLE_INDEX_NAME
        );
        // 设置排序
        $ots->orderBy('send_time', TableStoreChain::SORT_ASC, TableStoreChain::SORT_MODE_AVG);
        // 设置分页和返回字段
        $ots->offsetLimit(0, 200);
        $ots->select(['msgid', 'send_time']);

        $nextCursor = '';
        do {
            if (!empty($nextCursor)) {
                $ots->token($nextCursor);
            }
            // 执行查询 - 使用TableStoreChain的buildRequest方法
            $response = $ots->get();
            if (!empty($response['rows'])) {
                $data = [];
                foreach ($response['rows'] as $row) {
                    $data[$row['msgid']] = ['send_date' => date('Y-m-d', $row['send_time'])];
                }
                self::consoleLog($data);
                $tableStoreChain = new TableStoreChain(
                    OtsSuiteWorkWechatChatData::TABLE_NAME,
                    OtsSuiteWorkWechatChatData::TABLE_INDEX_NAME,
                    OtsSuiteWorkWechatChatData::PRIMARY_KEY
                );
                $updateData      = $tableStoreChain->batchUpdate($data);
                self::consoleLog($updateData);
                unset($updateData);
            }
            self::consoleLog('消息推送到：等待0s');
            $nextCursor = $response['next_token'] ?? '';
        } while ($nextCursor !== '');
        die;
    }

}