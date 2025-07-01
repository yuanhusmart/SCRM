<?php

namespace console\controllers;

use common\models\Account;
use common\models\SuiteCorpConfig;
use common\models\SuiteCorpExternalContact;
use common\services\SuiteCorpExternalContactService;
use common\services\SuiteCorpLicenseActiveInfoService;
use common\services\SuiteService;

class ExternalContactController extends BaseConsoleController
{

    const MAX_LIMIT_EXTERNAL_USERID = 100;


    /**
     * 外部联系人全量拉取   php ./yii external-contact/pull 4
     *
     * @param $id int 企业内部主键ID
     * @param int $id
     * @return bool
     */
    public function actionPull($id = 0)
    {
        self::consoleLog('外部联系人拉取开始');
        if (!$id) {
            self::consoleLog(">> 请输入企业内部ID}");
            return false;
        }
        $suiteConfig = SuiteCorpConfig::find()->asArray()->where(['id' => $id])->limit(1)->one();
        self::consoleLog('获取服务商配置数据');
        self::consoleLog($suiteConfig);

//        $authUserList = SuiteService::getAuthUserList($suiteConfig['suite_id'], $suiteConfig['corp_id']);
//        self::consoleLog('获取授权存档的成员列表');
//        self::consoleLog($authUserList);
//        if ($authUserList) {
//            $authUserList = array_column($authUserList['auth_user_list'], $authUserList['key']);
//        }


        try {
            $account = Account::find()->select(['userid'])
                              ->where(['suite_id' => $suiteConfig['suite_id'], 'corp_id' => $suiteConfig['corp_id']])
                //->andWhere(['in', 'userid', $authUserList])
                              ->orderBy(['id' => SORT_DESC])
                              ->asArray()
                              ->all();

            $i = 1;
            foreach ($account as $item) {
                self::consoleLog('企业ID:' . $suiteConfig['corp_id'] . ',Userid:' . $item['userid']);
                try {
                    $externalUserid = SuiteService::getExternalContactList($suiteConfig['suite_id'], $suiteConfig['corp_id'], $item['userid']);
                } catch (\Exception $ex) {
                    self::consoleLog($ex);
                }
                if (!empty($externalUserid['external_userid'])) {
                    foreach ($externalUserid['external_userid'] as $userid) {
                        try {
                            self::consoleLog($userid);
                            $details                                  = SuiteService::getExternalContactDetails($suiteConfig['suite_id'], $suiteConfig['corp_id'], $userid);
                            $details['external_contact']['suite_id']  = $suiteConfig['suite_id'];
                            $details['external_contact']['corp_id']   = $suiteConfig['corp_id'];
                            $details['external_contact']['is_modify'] = SuiteCorpExternalContact::IS_MODIFY_2;
                            SuiteCorpExternalContactService::create($details);
                            self::consoleLog('企业ID:' . $suiteConfig['corp_id'] . ',第 ' . $i . ' 次');
                        } catch (\Exception $createE) {
                            self::consoleLog($createE);
                        }
                        sleep(1);
                    }
                }
                $i++;
            }
        } catch (\Exception $e) {
            self::consoleLog($e);
        }
        self::consoleLog('外部联系人拉取结束');
        return true;
    }

    /**
     * 根据服务商接口调用许可信息拉取外部联系人 php ./yii external-contact/pull-by-license-active-info [可选入参企业ID]
     * @param $id int 企业内部主键ID
     * @param int $id
     * @return bool
     */
    public function actionPullByLicenseActiveInfo($id = 0)
    {
        self::consoleLog('根据服务商接口调用许可信息拉取外部联系人 - 开始');

        $corpExternalBatch = SuiteCorpLicenseActiveInfoService::getLicenseActiveGroupInfo(['id' => $id]);

        // 开始分组查询
        foreach ($corpExternalBatch as $corpItem) {
            self::consoleLog('根据企业ID进行分组数据:' . json_encode($corpItem, JSON_UNESCAPED_UNICODE));
            try {
                $externalUseridArray100 = array_chunk($corpItem['userid_list'], self::MAX_LIMIT_EXTERNAL_USERID);
                self::consoleLog($externalUseridArray100);
                self::consoleLog('根据企业ID进行分组数据 - 每次查询100个企业用户 :' . json_encode($externalUseridArray100, JSON_UNESCAPED_UNICODE));
                foreach ($externalUseridArray100 as $useridList) {
                    $param   = ['userid_list' => $useridList, 'limit' => self::MAX_LIMIT_EXTERNAL_USERID];
                    $iCursor = 1; // 游标执行次数
                    do {
                        if (!empty($nextCursor)) {
                            $param['cursor'] = $nextCursor;
                        }
                        $i             = 1;
                        $externalBatch = SuiteService::getBatchExternalContactDetails($corpItem['suite_id'], $corpItem['corp_id'], $param);
                        self::consoleLog('查询100个企业用户 - 批量接口结果 :' . json_encode($externalBatch, JSON_UNESCAPED_UNICODE));
                        foreach ($externalBatch['external_contact_list'] as $externalContactListValue) {
                            try {
                                $externalContactListValue['external_contact']['suite_id']  = $corpItem['suite_id'];
                                $externalContactListValue['external_contact']['corp_id']   = $corpItem['corp_id'];
                                $externalContactListValue['external_contact']['is_modify'] = SuiteCorpExternalContact::IS_MODIFY_2;
                                $externalContactListValue['follow_user']                   = [$externalContactListValue['follow_info']];
                                self::consoleLog('企业ID:' . $corpItem['corp_id'] . ',第 ' . $i . ' 次,客户的基本信息:' . json_encode($externalContactListValue, JSON_UNESCAPED_UNICODE));
                                SuiteCorpExternalContactService::create($externalContactListValue);
                                $i++;
                            } catch (\Exception $createE) {
                                self::consoleLog($createE);
                            }
                        }
                        $nextCursor = $externalBatch['next_cursor'] ?? '';
                        self::consoleLog('企业ID:' . $corpItem['corp_id'] . ',游标执行次数: ' . $iCursor);
                        $iCursor++;
                    } while ($nextCursor !== '');
                    // 初始化游标参数
                    unset($nextCursor);
                }
            } catch (\Exception $corpItemE) {
                self::consoleLog($corpItemE);
            }
        }
        self::consoleLog('根据服务商接口调用许可信息拉取外部联系人 - 结束');
        return true;
    }

}