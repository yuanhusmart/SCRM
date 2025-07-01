<?php

namespace console\controllers;


use common\errors\ErrException;
use common\services\SuiteCorpGroupChatService;
use common\services\SuiteCorpLicenseActiveInfoService;
use common\services\SuiteService;

class ExternalGroupController extends BaseConsoleController
{

    /**
     * 根据服务商接口调用许可信息拉取外部群组列表数据（获取客户群列表）
     * php ./yii external-group/list 1
     * @param int $corpId 企业配置项ID
     * @param int $corpId
     * @return true
     */
    public function actionList(int $corpId = 0)
    {
        self::consoleLog('获取全部 外部群组列表数据：开始');
        $corpExternalBatch = SuiteCorpLicenseActiveInfoService::getLicenseActiveGroupInfo(['id' => $corpId]);
        // 开始分组查询
        foreach ($corpExternalBatch as $corpItem) {
            self::consoleLog('根据企业ID进行分组数据:' . json_encode($corpItem, JSON_UNESCAPED_UNICODE));
            try {
                $externalUseridArray100 = array_chunk($corpItem['userid_list'], 100);
                self::consoleLog($externalUseridArray100);
                self::consoleLog('根据企业ID进行分组数据 - 每次查询100个企业用户 :' . json_encode($externalUseridArray100, JSON_UNESCAPED_UNICODE));
                foreach ($externalUseridArray100 as $useridList) {
                    $param   = ['status_filter' => 0, 'owner_filter' => ['userid_list' => $useridList], 'limit' => 1000];
                    $iCursor = 1; // 游标执行次数
                    do {
                        if (!empty($nextCursor)) {
                            $param['cursor'] = $nextCursor;
                        }
                        $list       = SuiteService::getExternalContactGroupChatList($corpItem['suite_id'], $corpItem['corp_id'], $param);
                        $nextCursor = self::handleGroup($list, ['suite_id' => $corpItem['suite_id'], 'corp_id' => $corpItem['corp_id']]);
                        self::consoleLog('游标第 ' . $iCursor . ' 次');
                        self::consoleLog('nextToken：' . $nextCursor);
                        $iCursor++;
                    } while ($nextCursor !== '');
                    // 初始化游标参数
                    unset($nextCursor);
                }
            } catch (\Exception $corpItemE) {
                self::consoleLog($corpItemE);
            }
        }
        self::consoleLog('获取全部 外部群组列表数据：结束');
        return true;
    }

    /**
     * 根据服务商接口调用许可信息拉取外部群组列表数据（获取客户群列表）
     * php ./yii external-group/list-all 4
     * @param int $corpId 企业配置项ID
     * @param int $corpId
     * @return true
     */
    public function actionListAll(int $corpId = 0)
    {
        self::consoleLog('获取全部 外部群组列表数据：开始');
        $corpExternalBatch = SuiteCorpLicenseActiveInfoService::getLicenseActiveGroupInfo(['id' => $corpId]);
        // 开始分组查询
        foreach ($corpExternalBatch as $corpItem) {
            self::consoleLog('根据企业ID进行分组数据:' . json_encode($corpItem, JSON_UNESCAPED_UNICODE));
            try {
                $param   = ['status_filter' => 0, 'limit' => 1000];
                $iCursor = 1; // 游标执行次数
                do {
                    if (!empty($nextCursor)) {
                        $param['cursor'] = $nextCursor;
                    }
                    $list       = SuiteService::getExternalContactGroupChatList($corpItem['suite_id'], $corpItem['corp_id'], $param);
                    $nextCursor = self::handleGroup($list, ['suite_id' => $corpItem['suite_id'], 'corp_id' => $corpItem['corp_id']]);
                    self::consoleLog('游标第 ' . $iCursor . ' 次');
                    self::consoleLog('nextToken：' . $nextCursor);
                    $iCursor++;
                } while ($nextCursor !== '');
                // 初始化游标参数
                unset($nextCursor);
            } catch (\Exception $corpItemE) {
                self::consoleLog($corpItemE);
            }
        }
        self::consoleLog('获取全部 外部群组列表数据：结束');
        return true;
    }

    /**
     * @param $params
     * @param $corp
     * @return string|null
     * @throws ErrException
     */
    public static function handleGroup($params, $corp): ?string
    {
        self::consoleLog('handleGroup ------------------------------------');
        self::consoleLog($params);
        if (!empty($params['group_chat_list'])) {
            self::consoleLog('group_chat_list 存在');
            foreach ($params['group_chat_list'] as $groupChatList) {
                $details             = SuiteService::getExternalContactGroupChat($corp['suite_id'], $corp['corp_id'], $groupChatList['chat_id']);
                $details             = $details['group_chat'];
                $details['suite_id'] = $corp['suite_id'];
                $details['corp_id']  = $corp['corp_id'];
                self::consoleLog('handleGroupDetails------------------------------------' . json_encode($details, JSON_UNESCAPED_UNICODE));
                SuiteCorpGroupChatService::create($details);
                unset($details);
            }
        }
        return $params['next_cursor'] ?? '';
    }

}