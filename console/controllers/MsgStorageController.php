<?php

namespace console\controllers;

use common\models\OtsSuiteWorkWechatChatData;
use common\sdk\TableStoreChain;
use common\services\OtsSuiteWorkWechatChatDataService;
use common\services\Service;

class MsgStorageController extends BaseConsoleController
{
    /**
     * 解密消息密钥
     * @param string $encryptedSecretKey 加密的密钥
     * @return string 解密后的密钥
     */
    private function decryptSecretKey($encryptedSecretKey)
    {
        $decryptedData = '';
        openssl_private_decrypt(
            base64_decode($encryptedSecretKey),
            $decryptedData,
            \Yii::$app->params["workWechat"]['privateKey']
        );
        return $decryptedData;
    }

    /**
     * 处理消息字段
     * @param array $data 原始消息数据
     * @return array 处理后的消息数据
     */
    private function processMessageFields($data)
    {
        // 字段处理映射
        $fieldHandlers = [
            // 消息发送者
            'sender'               => function ($value, &$data) {
                $data['sender_type'] = $value['type'];
                $data['sender_id']   = $value['id'];
                unset($data['sender']);
            },
            // 加密的消息数据
            'service_encrypt_info' => function ($value, &$data) {
                $data['msg_encrypted_secret_key'] = $value['encrypted_secret_key'];
                $data['msg_public_key_ver']       = $value['public_key_ver'];
                $data['msg_decrypted_secret_key'] = $this->decryptSecretKey($value['encrypted_secret_key']);
                unset($data['service_encrypt_info']);
            },
            // 通话时长，单位秒。仅当消息类型为"音视频通话"或"音频存档"时返回
            'extra_info'           => function ($value, &$data) {
                if (!empty($value['call_duration'])) {
                    $data['extra_info_call_duration'] = $value['call_duration'];
                    unset($data['extra_info']);
                }
            }
        ];

        // 处理每个字段
        foreach ($data as $key => $value) {
            if (isset($fieldHandlers[$key])) {
                $fieldHandlers[$key]($value, $data);
            }
        }

        return $data;
    }

    /**
     * 企业微信 消息存储
     * php ./yii msg-storage/to-ots
     *
     * Supervisor:aaw.msg-storage.to-ots [ supervisorctl restart aaw.msg-storage.to-ots: ]
     * Supervisor Log:/var/log/supervisor/aaw.msg-storage.to-ots.log
     * @return void
     */
    public function actionToOts()
    {
        Service::consoleConsumptionMQ(
            OtsSuiteWorkWechatChatDataService::MQ_FAN_OUT_EXCHANGE_CHAT_MSG,
            OtsSuiteWorkWechatChatDataService::MQ_FAN_OUT_QUEUE_CHAT_MSG_DATA,
            function ($data) {
                self::consoleLog($data);
                try {
                    // 预处理消息结构体
                    $data = $this->processMessageFields($data);

                    $tableStoreChain = new TableStoreChain(
                        OtsSuiteWorkWechatChatData::TABLE_NAME,
                        OtsSuiteWorkWechatChatData::TABLE_INDEX_NAME,
                        OtsSuiteWorkWechatChatData::PRIMARY_KEY
                    );
                    $resp            = $tableStoreChain->insert($data);
                    self::consoleLog($resp);
                } catch (\Exception $e) {
                    self::consoleLog('消息ID：' . $data[OtsSuiteWorkWechatChatData::PRIMARY_KEY] . ',跳过' . $e->getMessage());
                }
            },
            1,
            OtsSuiteWorkWechatChatDataService::MQ_FAN_OUT_ROUTING_KEY_CHAT_MSG,
            AMQP_EX_TYPE_FANOUT
        );
    }
}