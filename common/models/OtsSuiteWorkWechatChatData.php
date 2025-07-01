<?php

namespace common\models;

use Aliyun\OTS\Consts\ColumnTypeConst;
use Aliyun\OTS\Consts\FieldTypeConst;
use Aliyun\OTS\Consts\PrimaryKeyTypeConst;
use common\sdk\TableStoreChain;

/**
 * 表格存储，服务商企业微信消息数据
 *
 * @property string $msgid 消息ID
 * @property string $suite_id 服务商ID
 * @property string $corp_id 企业ID
 * @property string $session_id 会话ID
 * @property string $chatid 群ID，当消息是群消息的时候会返回该字段
 * @property int $msgtype 消息类型。枚举值定义见下方消息类型
 * @property int $send_time 消息发送时间对应的unix时间戳
 * @property int $sender_type 消息发送者身份类型。1：员工；2：外部联系人; 3：机器人
 * @property string $sender_id 消息发送者的id，当消息发送者为员工时，该字段为员工的userid；当消息发送者的身份为外部联系人时，该字段为外部联系人的id
 * @property int $msg_public_key_ver 公钥版本号
 * @property string $msg_encrypted_secret_key 加密后的密钥，使用设置公钥设置的公钥进行加密，需要应用后台用私钥解密后，才可在其他接口使用，例如模型分析接口等)
 * @property string $msg_decrypted_secret_key 私钥解密后的密钥
 * @property array $receiver_list 消息接收者列表。当自己发给自己消息时该字段为发送者ID，其他情况不包含发送者
 * @property array-key|null $type 消息接收者的身份类型。1：员工；2：外部联系人; 3：机器人
 * @property array-key|null $id 当接收者身份类型为员工时，该字段为员工userid；当接收者身份类型为外部联系人时，该字段为外部联系人id；当接收者身份类型为机器人式为机器人ID
 */
class OtsSuiteWorkWechatChatData
{

    /**
     * pk 主键
     */
    const PRIMARY_KEY = 'msgid';

    const TABLE_NAME = 'ots_suite_work_wechat_chat_data';

    const TABLE_INDEX_NAME = 'ots_suite_work_wechat_chat_data_index';


    //  消息身份类型。1：员工；2：外部联系人; 3：机器人;
    const USER_TYPE_1 = 1;
    const USER_TYPE_2 = 2;
    const USER_TYPE_3 = 3;


    // 指定客户会话类型:1单聊 2群聊
    const CHAT_TYPE_1 = 1;
    const CHAT_TYPE_2 = 2;

    const MSG_TYPE_0  = 0;
    const MSG_TYPE_1  = 1;
    const MSG_TYPE_2  = 2;
    const MSG_TYPE_3  = 3;
    const MSG_TYPE_4  = 4;
    const MSG_TYPE_5  = 5;
    const MSG_TYPE_6  = 6;
    const MSG_TYPE_7  = 7;
    const MSG_TYPE_8  = 8;
    const MSG_TYPE_9  = 9;
    const MSG_TYPE_10 = 10;
    const MSG_TYPE_11 = 11;
    const MSG_TYPE_12 = 12;
    const MSG_TYPE_13 = 13;
    const MSG_TYPE_14 = 14;
    const MSG_TYPE_15 = 15;
    const MSG_TYPE_16 = 16;
    const MSG_TYPE_17 = 17;
    const MSG_TYPE_18 = 18;
    const MSG_TYPE_19 = 19;
    const MSG_TYPE_20 = 20;
    const MSG_TYPE_21 = 21;
    const MSG_TYPE_22 = 22;
    const MSG_TYPE_23 = 23;
    const MSG_TYPE_24 = 24;
    const MSG_TYPE_25 = 25;
    const MSG_TYPE_26 = 26;
    const MSG_TYPE_27 = 27;
    const MSG_TYPE_28 = 28;
    const MSG_TYPE_30 = 30;
    const MSG_TYPE_31 = 31;

    const MSG_TYPE_DESC_0  = '暂不支持的消息类型';
    const MSG_TYPE_DESC_1  = "文本";
    const MSG_TYPE_DESC_2  = "图片";
    const MSG_TYPE_DESC_3  = "表情";
    const MSG_TYPE_DESC_4  = "链接";
    const MSG_TYPE_DESC_5  = "小程序";
    const MSG_TYPE_DESC_6  = "语音";
    const MSG_TYPE_DESC_7  = "视频";
    const MSG_TYPE_DESC_8  = "文件";
    const MSG_TYPE_DESC_9  = "名片";
    const MSG_TYPE_DESC_10 = "转发消息";
    const MSG_TYPE_DESC_11 = "视频号";
    const MSG_TYPE_DESC_12 = "日程";
    const MSG_TYPE_DESC_13 = "红包";
    const MSG_TYPE_DESC_14 = "地理位置";
    const MSG_TYPE_DESC_15 = "快速会议";
    const MSG_TYPE_DESC_16 = "待办";
    const MSG_TYPE_DESC_17 = "投票";
    const MSG_TYPE_DESC_18 = "在线文档";
    const MSG_TYPE_DESC_19 = "图文消息";
    const MSG_TYPE_DESC_20 = "图文混合消息";
    const MSG_TYPE_DESC_21 = "音频存档";
    const MSG_TYPE_DESC_22 = "音视频通话";
    const MSG_TYPE_DESC_23 = "微盘文件";
    const MSG_TYPE_DESC_24 = "同意会话存档";
    const MSG_TYPE_DESC_25 = "拒绝会话存档";
    const MSG_TYPE_DESC_26 = "群接龙";
    const MSG_TYPE_DESC_27 = "markdown";
    const MSG_TYPE_DESC_28 = "笔记";
    const MSG_TYPE_DESC_30 = "系统消息";
    const MSG_TYPE_DESC_31 = "撤回消息";

    // 消息类型
    const MSG_TYPE = [
        self::MSG_TYPE_0  => self::MSG_TYPE_DESC_0,
        self::MSG_TYPE_1  => self::MSG_TYPE_DESC_1,
        self::MSG_TYPE_2  => self::MSG_TYPE_DESC_2,
        self::MSG_TYPE_3  => self::MSG_TYPE_DESC_3,
        self::MSG_TYPE_4  => self::MSG_TYPE_DESC_4,
        self::MSG_TYPE_5  => self::MSG_TYPE_DESC_5,
        self::MSG_TYPE_6  => self::MSG_TYPE_DESC_6,
        self::MSG_TYPE_7  => self::MSG_TYPE_DESC_7,
        self::MSG_TYPE_8  => self::MSG_TYPE_DESC_8,
        self::MSG_TYPE_9  => self::MSG_TYPE_DESC_9,
        self::MSG_TYPE_10 => self::MSG_TYPE_DESC_10,
        self::MSG_TYPE_11 => self::MSG_TYPE_DESC_11,
        self::MSG_TYPE_12 => self::MSG_TYPE_DESC_12,
        self::MSG_TYPE_13 => self::MSG_TYPE_DESC_13,
        self::MSG_TYPE_14 => self::MSG_TYPE_DESC_14,
        self::MSG_TYPE_15 => self::MSG_TYPE_DESC_15,
        self::MSG_TYPE_16 => self::MSG_TYPE_DESC_16,
        self::MSG_TYPE_17 => self::MSG_TYPE_DESC_17,
        self::MSG_TYPE_18 => self::MSG_TYPE_DESC_18,
        self::MSG_TYPE_19 => self::MSG_TYPE_DESC_19,
        self::MSG_TYPE_20 => self::MSG_TYPE_DESC_20,
        self::MSG_TYPE_21 => self::MSG_TYPE_DESC_21,
        self::MSG_TYPE_22 => self::MSG_TYPE_DESC_22,
        self::MSG_TYPE_23 => self::MSG_TYPE_DESC_23,
        self::MSG_TYPE_24 => self::MSG_TYPE_DESC_24,
        self::MSG_TYPE_25 => self::MSG_TYPE_DESC_25,
        self::MSG_TYPE_26 => self::MSG_TYPE_DESC_26,
        self::MSG_TYPE_27 => self::MSG_TYPE_DESC_27,
        self::MSG_TYPE_28 => self::MSG_TYPE_DESC_28,
        //self::MSG_TYPE_30 => self::MSG_TYPE_DESC_30,
        //self::MSG_TYPE_31 => self::MSG_TYPE_DESC_31,
    ];

    /**
     * @return array
     */
    public static function tableSchema(): array
    {
        return [
            "table_meta"    => [
                "table_name"         => self::TABLE_NAME,
                "primary_key_schema" => [
                    [self::PRIMARY_KEY, PrimaryKeyTypeConst::CONST_STRING]
                ],
                "defined_column"     => [
                    ["suite_id", ColumnTypeConst::CONST_STRING],
                    ["corp_id", ColumnTypeConst::CONST_STRING],
                    ["session_id", ColumnTypeConst::CONST_STRING],
                    ["chatid", ColumnTypeConst::CONST_STRING],
                    ["sender_id", ColumnTypeConst::CONST_STRING],
                    ["msgtype", ColumnTypeConst::CONST_INTEGER],
                    ["sender_type", ColumnTypeConst::CONST_INTEGER],
                    ["send_time", ColumnTypeConst::CONST_INTEGER]
                ]
            ],
            "table_options" => [
                "time_to_live"                  => -1,
                "max_versions"                  => 1,
                "deviation_cell_version_in_sec" => 86400,
                "allow_update"                  => true
            ],
        ];
    }

    /**
     * @return array[]
     */
    public static function indexSchema(): array
    {
        return [
            "field_schemas" => [
                TableStoreChain::createKeywordField("msgid"),
                TableStoreChain::createKeywordField("suite_id"),
                TableStoreChain::createKeywordField("corp_id"),
                TableStoreChain::createKeywordField("session_id"),
                TableStoreChain::createKeywordField("chatid"),
                TableStoreChain::createLongField("msgtype"),
                TableStoreChain::createLongField("send_time"),
                TableStoreChain::createDateField("send_date", ['yyyy-MM-dd'], true),
                TableStoreChain::createKeywordField("sender_id"),
                TableStoreChain::createLongField("sender_type"),
                // 嵌套字段 创建接收者列表字段
                TableStoreChain::createNestedField("receiver_list", [
                    // 创建接收者列表的子字段
                    TableStoreChain::createFieldSchema("type", FieldTypeConst::LONG, true, true, true),
                    TableStoreChain::createFieldSchema("id", FieldTypeConst::KEYWORD, true, true, true)
                ]),
                TableStoreChain::createKeywordField("msg_encrypted_secret_key"),
                TableStoreChain::createLongField("msg_public_key_ver"),
                TableStoreChain::createKeywordField("msg_decrypted_secret_key"),
            ]
        ];
    }


}
