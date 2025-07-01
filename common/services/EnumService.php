<?php

namespace common\services;

use common\models\SuiteCorpConfig;
use common\models\SuiteCorpSemantics;

class EnumService
{
    public static function get()
    {
        return [
            'OSS_DEFAULT'                                                            => env('OSS_DEFAULT', ''),
            'MSG_TYPE'                                                               => \common\models\OtsSuiteWorkWechatChatData::MSG_TYPE,
            'SUITE'                                                                  => [
                'AI_APP' => [
                    'id'   => \Yii::$app->params['workWechat']['suiteId'],
                    'name' => \Yii::$app->params['workWechat']['suiteName']
                ],
                'LOGIN'  => [
                    'id' => \Yii::$app->params['workWechat']['loginAuthSuiteId']
                ]
            ],
            'SUBJECT_TYPE_DESC'                                                      => SuiteCorpConfig::SUBJECT_TYPE_DESC,
            'CORP_TYPE_DESC'                                                         => SuiteCorpConfig::CORP_TYPE_DESC,
            'SEMANTICS_DESC'                                                         => SuiteCorpSemantics::SEMANTICS_DESC,
            'ACCOUNT_STATUS'                                                         => \common\models\Account::ACCOUNT_STATUS,
            'EXTERNAL_CONTACT_ADD_WAY'                                               => \common\models\SuiteCorpExternalContactFollowUser::ENUM_ADD_WAY,
            // 模型.字段
            'suite_corp_account'                                                     => [
                'status' => \common\models\concerns\enums\SuiteCorpAccount\Status::enum()
            ],
            'suite_chat_setting'                                                     => [
                'key'   => \common\models\concerns\enums\SuiteChatSetting\Key::enum(),
                'group' => \common\models\concerns\enums\SuiteChatSetting\Group::enum(),
            ],
            'suite_wechat_login_log'                                                 => [
                'status' => \common\models\concerns\enums\SuiteWechatLoginLog\Status::enum(),
            ],
            \common\models\SuiteCorpIndustry::tableName()                            => [
                'status'      => \common\models\SuiteCorpIndustry::STATUS_MAP,
                'grade_style' => \common\models\SuiteCorpIndustry::GRADE_STYLE_MAP,
            ],
            \common\models\SuiteCorpAiOpportunitiesSet::tableName()                  => [
                'status' => \common\models\SuiteCorpAiOpportunitiesSet::STATUS_MAP,
            ],
            \common\models\SuiteCorpAiOpportunitiesSetConfig::tableName()            => [
                'status' => \common\models\SuiteCorpAiOpportunitiesSetConfig::STATUS_MAP,
            ],
            \common\models\SuiteCorpIndustryContactRole::tableName()                 => [
                'status' => \common\models\SuiteCorpIndustryContactRole::STATUS_MAP,
            ],
            \common\models\SuiteCorpIndustryCustomerStatus::tableName()              => [
                'status' => \common\models\SuiteCorpIndustryCustomerStatus::STATUS_MAP,
            ],
            \common\models\SuiteKnowledgeBase::tableName()                           => [
                'type' => \common\models\SuiteKnowledgeBase::TYPE_MAP,
                'purpose' => \common\models\SuiteKnowledgeBase::PURPOSE_MAP,
            ],
            \common\models\SuiteAgent::tableName()                                   => [
                'status'     => \common\models\SuiteAgent::STATUS_MAP,
                'agent_type' => \common\models\SuiteAgent::AGENT_TYPE_MAP,
            ],
            \common\models\SuiteAgentAbility::tableName()                                   => [
                'model_type' => \common\models\SuiteAgentAbility::MODEL_TYPE_MAP,
                'purpose' => \common\models\SuiteAgentAbility::PURPOSE_MAP,
            ],
            \common\models\crm\SuiteCorpCrmCustomer::tableName()                     => [
                'source' => \common\models\crm\SuiteCorpCrmCustomer::SOURCE_MAP,
            ],
            \common\models\crm\SuiteCorpCrmCustomerLink::tableName()                 => [
                'relational' => \common\models\crm\SuiteCorpCrmCustomerLink::RELATIONAL_MAP,
            ],
            \common\models\crm\SuiteCorpCrmCustomerContactInformation::tableName()   => [
                'contact_information_type' => \common\models\crm\SuiteCorpCrmCustomerContactInformation::CONTACT_INFORMATION_TYPE_MAP,
            ],
            \common\models\crm\SuiteCorpCrmBusinessOpportunities::tableName()        => [
                'source'    => \common\models\crm\SuiteCorpCrmBusinessOpportunities::SOURCE_MAP,
                'status'    => \common\models\crm\SuiteCorpCrmBusinessOpportunities::STATUS_MAP,
                'loss_risk' => \common\models\crm\SuiteCorpCrmBusinessOpportunities::LOSS_RISK_MAP,
            ],
            \common\models\crm\SuiteCorpCrmBusinessOpportunitiesLink::tableName()    => [
                'relational' => \common\models\crm\SuiteCorpCrmBusinessOpportunitiesLink::RELATIONAL_MAP,
            ],
            \common\models\crm\SuiteCorpCrmBusinessOpportunitiesContact::tableName() => [
                'is_main' => \common\models\crm\SuiteCorpCrmBusinessOpportunitiesContact::IS_MAIN_MAP,
            ],
            'suite_package'                                                          => [
                'type' => \common\models\concerns\enums\SuitePackage\Type::enum(),
            ],
            'suite_role'                                                             => [
                'type' => \common\models\concerns\enums\SuiteRole\Type::enum(),
            ],
            'suite_attach_permission'                                                => [
                'type'      => \common\models\concerns\enums\SuiteAttachPermission\Type::enum(),
                'time_type' => \common\models\concerns\enums\SuiteAttachPermission\TimeType::enum(),
            ],
        ];
    }
}

