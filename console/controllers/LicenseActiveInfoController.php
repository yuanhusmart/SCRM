<?php

namespace console\controllers;


use common\models\SuiteCorpConfig;
use common\models\SuiteCorpExternalContact;
use common\models\SuiteCorpGroupChat;
use common\models\SuiteCorpGroupChatMember;
use common\models\SuiteCorpLicenseActiveInfo;
use common\services\SuiteCorpExternalContactService;
use common\services\SuiteCorpGroupChatService;
use common\services\SuiteService;


class LicenseActiveInfoController extends BaseConsoleController
{

    /**
     * 获取近期变更接口调用许可列表：进行好友和群数据拉取
     * PS：若当前用户开通了互通接口，则需要晚上去定时跑一遍他的好友和群数据
     * 定时任务 php ./yii license-active-info/pull-external
     * 执行时机：每天凌晨3:40执行
     * @param int $days 企业主键ID 不传入默认拉取所有企业
     * @return bool
     */
    public function actionPullExternal(int $days = 1)
    {
        self::consoleLog(">> 获取近期变更接口调用许可列表：进行好友和群数据拉取开始 }");
        try {
            $time = time();
            $sub  = $days * 24 * 60 * 60; // 默认查询1天数据

            foreach (SuiteCorpConfig::find()->select('suite_id,corp_id')->asArray()->all() as $configItem) {

                self::consoleLog('获取服务商配置数据');
                self::consoleLog($configItem);

                $batchSize = ExternalContactController::MAX_LIMIT_EXTERNAL_USERID;
                foreach (SuiteCorpLicenseActiveInfo::find()
                                                   ->select('id,userid')
                                                   ->andWhere(['suite_id' => $configItem['suite_id']])
                                                   ->andWhere(['corp_id' => $configItem['corp_id']])
                                                   ->andWhere(['status' => SuiteCorpLicenseActiveInfo::STATUS_2])
                                                   ->andWhere([
                                                       'OR',
                                                       ['between', 'active_time', $time - $sub, $time],
                                                       ['between', 'updated_active_time', $time - $sub, $time],
                                                   ])
                                                   ->asArray()
                                                   ->batch($batchSize) as $activeInfo) {


                    /* TODO 开始 按照企业进行分组 每100人进行批量查询 单次查询后进行游标验证是否结束 */
                    $param = [
                        'userid_list' => array_column($activeInfo, 'userid'),
                        'limit'       => $batchSize
                    ];

                    self::consoleLog('处理外部联系人-开始');

                    $iCursor = 1; // 游标执行次数
                    do {
                        if (!empty($nextCursor)) {
                            $param['cursor'] = $nextCursor;
                        }
                        $i             = 1;
                        $externalBatch = SuiteService::getBatchExternalContactDetails($configItem['suite_id'], $configItem['corp_id'], $param);
                        self::consoleLog('查询100个企业用户 - 批量接口结果 :' . json_encode($externalBatch, JSON_UNESCAPED_UNICODE));
                        foreach ($externalBatch['external_contact_list'] as $externalContactListValue) {
                            try {
                                $externalContactListValue['external_contact']['suite_id']  = $configItem['suite_id'];
                                $externalContactListValue['external_contact']['corp_id']   = $configItem['corp_id'];
                                $externalContactListValue['external_contact']['is_modify'] = SuiteCorpExternalContact::IS_MODIFY_2;
                                $externalContactListValue['follow_user']                   = [$externalContactListValue['follow_info']];
                                self::consoleLog('企业ID:' . $configItem['corp_id'] . ',第 ' . $i . ' 次,客户的基本信息:' . json_encode($externalContactListValue, JSON_UNESCAPED_UNICODE));
                                SuiteCorpExternalContactService::create($externalContactListValue);
                                $i++;
                            } catch (\Exception $createE) {
                                self::consoleLog($createE);
                            }
                        }
                        $nextCursor = $externalBatch['next_cursor'] ?? '';
                        self::consoleLog('企业ID:' . $configItem['corp_id'] . ',游标执行次数: ' . $iCursor);
                        $iCursor++;
                    } while ($nextCursor !== '');
                    // 初始化游标参数
                    unset($nextCursor);
                    /* TODO 结束 按照企业进行分组 每100人进行批量查询  */

                    self::consoleLog('处理外部联系人-结束');

                    self::consoleLog('--------------------------------');

                    self::consoleLog('处理外部群组-开始');
                    foreach ($activeInfo as $activeItem) {
                        $chats = SuiteCorpGroupChat::find()
                                                   ->select('chat_id')
                                                   ->andWhere(['suite_id' => $configItem['suite_id']])
                                                   ->andWhere(['corp_id' => $configItem['corp_id']])
                                                   ->andWhere(['group_type' => SuiteCorpGroupChat::GROUP_TYPE_1])
                                                   ->andWhere(['Exists',
                                                       SuiteCorpGroupChatMember::find()
                                                                               ->andWhere(SuiteCorpGroupChat::tableName() . ".id=" . SuiteCorpGroupChatMember::tableName() . ".group_chat_id")
                                                                               ->andWhere([SuiteCorpGroupChatMember::tableName() . '.type' => SuiteCorpGroupChatMember::GROUP_CHAT_TYPE_1])
                                                                               ->andWhere([SuiteCorpGroupChatMember::tableName() . '.userid' => $activeItem['userid']])
                                                   ])
                                                   ->groupBy(['chat_id'])
                                                   ->asArray()
                                                   ->all();

                        foreach ($chats as $chat) {
                            $details = SuiteService::getExternalContactGroupChat($configItem['suite_id'], $configItem['corp_id'], $chat['chat_id']);
                            if ($details) {
                                $details             = $details['group_chat'];
                                $details['suite_id'] = $configItem['suite_id'];
                                $details['corp_id']  = $configItem['corp_id'];
                                self::consoleLog('新增外部群组数据：' . json_encode($details, JSON_UNESCAPED_UNICODE));
                                $groupChatId = SuiteCorpGroupChatService::create($details);
                                self::consoleLog('新增外部群组数据ID：' . $groupChatId);
                            }
                            unset($details);
                        }
                    }

                    self::consoleLog('处理外部群组-结束');
                }
            }
        } catch (\Exception $e) {
            self::consoleLog($e->getMessage());
        }
        self::consoleLog(">> 获取近期变更接口调用许可列表：进行好友和群数据拉取：全部完成}");
        return true;
    }

}