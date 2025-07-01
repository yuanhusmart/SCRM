<?php

namespace common\models\concerns\widgets\Account;

use yii\db\Expression;

class Sql
{
    /**
     * 接收红包次数
     */
    public static function redPacketReceiveCount()
    {
        $sql = <<<SQL
(select count(*)
 from suite_corp_hit_msg as hm
          left join suite_corp_hit_msg_semantics as semantics on hm.id = semantics.hit_msg_id
 where hm.receiver_id = a.userid
   and hm.receiver_type = 1
   and semantics.semantics = 1
   and hm.updated_at > UNIX_TIMESTAMP() - (30 * 24 * 60 * 60)
 )
as red_packet_receive_count
SQL;

        return new Expression($sql);
    }

    /**
     * 触发敏感词
     */
    public static function triggerSensitiveWordCount()
    {
        $sql = <<<SQL
(select count(*)
 from suite_corp_hit_msg as hm
 where hm.receiver_id = a.userid
   and hm.receiver_type = 1 
   and hm.has_hit_keyword = 1
   and hm.updated_at > UNIX_TIMESTAMP() - (30 * 24 * 60 * 60)
 )
as trigger_sensitive_word_count
SQL;

        return new Expression($sql);
    }

    /**
     * 发送银行卡
     */
    public static function sendBankCardCount()
    {
        $sql = <<<SQL
(select count(*)
 from suite_corp_hit_msg as hm
          left join suite_corp_hit_msg_semantics as semantics on hm.id = semantics.hit_msg_id
 where hm.sender_id = a.userid
   and hm.sender_type = 1
   and semantics.semantics = 7
   and hm.updated_at > UNIX_TIMESTAMP() - (30 * 24 * 60 * 60)
 )
as send_bank_card_count
SQL;
        return new Expression($sql);
    }

    /**
     * 发送身份证
     */
    public static function sendIdCardCount()
    {
        $sql = <<<SQL
(select count(*)
 from suite_corp_hit_msg as hm
          left join suite_corp_hit_msg_semantics as semantics on hm.id = semantics.hit_msg_id
 where hm.sender_id = a.userid
   and hm.sender_type = 1
   and semantics.semantics = 8
   and hm.updated_at > UNIX_TIMESTAMP() - (30 * 24 * 60 * 60)
 )
as send_id_card_count
SQL;
        return new Expression($sql);
    }

    /**
     * 流失商机
     */
    public static function lostBusinessCount()
    {
        $sql = <<<SQL
(select count(*)
 from suite_corp_crm_business_opportunities as bo
          left join suite_corp_crm_business_opportunities_link lk on bo.business_opportunities_no=lk.business_opportunities_no 
          and lk.relational=1 and lk.account_id=a.id
 where bo.created_at > UNIX_TIMESTAMP() - (30 * 24 * 60 * 60))
as lost_business_count
SQL;
        return new Expression($sql);
    }
}

