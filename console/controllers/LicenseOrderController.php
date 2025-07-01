<?php

namespace console\controllers;


use common\models\Account;
use common\models\SuiteCorpConfig;
use common\models\SuiteCorpLicenseActiveInfo;
use common\models\SuiteCorpLicenseOrder;
use common\services\SuiteCorpLicenseActiveInfoService;
use common\services\SuiteCorpLicenseOrderService;

class LicenseOrderController extends BaseConsoleController
{

    /**
     * 接口调用许可 订单数据拉取
     * 定时任务 php ./yii license-order/pull
     * @return true
     */
    public function actionPull()
    {
        self::consoleLog(">> 开始}");
        try {
            self::consoleLog('获取服务商配置数据');
            SuiteCorpLicenseOrderService::licenseOrderPull();
        } catch (\Exception $e) {
            self::consoleLog($e->getMessage());
        }
        self::consoleLog(">> 全部完成}");
        return true;
    }

    /**
     * 接口调用许可 订单数据 许可证数据拉取
     * 定时任务 php ./yii license-order/account-pull
     * @return true
     */
    public function actionAccountPull()
    {
        self::consoleLog(">> 开始}");
        try {
            $orderList = SuiteCorpLicenseOrder::find()->select('order_id')->andWhere(['order_status' => SuiteCorpLicenseOrder::ORDER_STATUS_1])->asArray()->all();
            self::consoleLog($orderList);
            foreach ($orderList as $value) {
                SuiteCorpLicenseOrderService::syncAccountByOrderId($value['order_id']);
            }
        } catch (\Exception $e) {
            self::consoleLog($e->getMessage());
        }
        self::consoleLog(">> 全部完成}");
        return true;
    }

    /**
     * 调用许可 执行自动授权
     * 定时任务 php ./yii license-order/is-auto-auth-execute
     * @return true
     */
    public function actionIsAutoAuthExecute()
    {
        self::consoleLog(">> 开始}");
        try {

            /**
             *  ⚠️ TODO 所有逻辑由上至下执行
             *
             *  一、需要取消互通账号的有如下场景：
             *       1.激活码 状态：未绑定
             *       2.激活码 状态：待转移
             *       3.查询 存在互通账号激活码（状态：已绑定且有效） && 最新激活绑定用户的时间>30天 && 未开启会话存档 && 开启自动授权 && 企微账号状态(已激活OR已禁用)并未删除
             *       PS : 优先级（由上至下），如果进行互通账号【继承功能】时 等待继承的用户群体不满足则取消执行
             *
             *       等待继承的用户群体如下（优先级 由上至下）：
             *           1.账号状态已激活并未删除 && 开启会话存档 && 无互通账号 && 无普通账号
             *
             *  二、需要取消普通账号的有如下场景（优先级 由上至下，如果进行普通账号【继承功能】时 等待继承的用户群体不满足则取消执行）：
             *       1.激活码 状态：未绑定
             *       2.激活码 状态：待转移
             *
             *      等待继承的用户群体如下（优先级 由上至下）：
             *           1.账号状态已激活并未删除 && 开启会话存档 && 无互通账号 && 无普通账号
             *           2.账号状态已激活并未删除 && 无互通账号 && 无普通账号
             *
             */

            //  一、需要取消互通账号的有如下场景：等待继承的用户群体
            $waitForInheritUser = Account::find()
                                         ->select('suite_id,corp_id,config_id,userid,nickname')
                                        // TODO 存档
                                         ->andWhere(["status" => Account::ACCOUNT_STATUS_1])
                                         ->andWhere(["deleted_at" => 0])
                                         ->andWhere(['NOT EXISTS',
                                             SuiteCorpLicenseActiveInfo::find()
                                                                       ->select(SuiteCorpLicenseActiveInfo::tableName() . '.id')
                                                                       ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".suite_id=" . Account::tableName() . ".suite_id")
                                                                       ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".corp_id=" . Account::tableName() . ".corp_id")
                                                                       ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".userid=" . Account::tableName() . ".userid")
                                                                       ->andWhere(['IN', SuiteCorpLicenseActiveInfo::tableName() . ".type", [SuiteCorpLicenseActiveInfo::TYPE_1, SuiteCorpLicenseActiveInfo::TYPE_2]])
                                                                       ->andWhere([SuiteCorpLicenseActiveInfo::tableName() . '.status' => SuiteCorpLicenseActiveInfo::STATUS_2])
                                         ])
                                         ->asArray()
                                         ->all();

            self::logicCode($waitForInheritUser);

        } catch (\Exception $e) {
            self::consoleLog($e->getMessage());
        }
        self::consoleLog(">> 全部完成}");
        return true;
    }

    /**
     * @param $waitForInheritUser
     * @return true
     * @throws \common\errors\ErrException
     */
    public static function logicCode($waitForInheritUser = null)
    {
        if (!empty($waitForInheritUser)) {
            foreach ($waitForInheritUser as $waitItem) {

                $config = SuiteCorpConfig::find()->select('id,suite_id,corp_id,corp_name')->andWhere(['id' => $waitItem['config_id']])->limit(1)->asArray()->one();
                self::consoleLog('开始执行【企业配置】,数据：' . json_encode($config, JSON_UNESCAPED_UNICODE));
                if (empty($config)) {
                    continue;
                }

                self::consoleLog('开始执行【等待继承的用户群体】,数据：' . json_encode($waitItem, JSON_UNESCAPED_UNICODE));

                // 1.激活码 状态：未绑定
                $active = SuiteCorpLicenseActiveInfo::find()
                                                    ->andWhere([
                                                        'suite_id' => $config['suite_id'],
                                                        'corp_id'  => $config['corp_id'],
                                                        'status'   => SuiteCorpLicenseActiveInfo::STATUS_1,
                                                        'type'     => SuiteCorpLicenseActiveInfo::TYPE_2
                                                    ])
                                                    ->limit(1)
                                                    ->asArray()
                                                    ->one();

                // 2.激活码 状态：待转移
                $active = SuiteCorpLicenseActiveInfo::find()
                                                    ->andWhere([
                                                        'suite_id' => $config['suite_id'],
                                                        'corp_id'  => $config['corp_id'],
                                                        'status'   => SuiteCorpLicenseActiveInfo::STATUS_4,
                                                        'type'     => SuiteCorpLicenseActiveInfo::TYPE_2
                                                    ])
                                                    ->limit(1)
                                                    ->asArray()
                                                    ->one();
                if (!empty($active)) {
                    self::consoleLog('开始执行：2.激活码 状态：待转移，数据：' . json_encode($active, JSON_UNESCAPED_UNICODE));

                    $user = Account::find()->select('id,userid,jnumber,nickname')->andWhere(['suite_id' => $config['suite_id'], 'corp_id' => $config['corp_id'], 'userid' => $active['userid']])->limit(1)->asArray()->one();
                    self::consoleLog('开始执行：2.用户数据：' . json_encode($user, JSON_UNESCAPED_UNICODE));

                    try {
                        $data = SuiteCorpLicenseOrderService::batchTransferLicense([
                            'corpid'        => $config['corp_id'],
                            'transfer_list' => [['handover_userid' => $active['userid'], 'takeover_userid' => $waitItem['userid']]]
                        ]);
                        self::consoleLog($data);
                    } catch (\Exception $exception) {
                        if (strpos($exception->getMessage(), '企微微信请求失败：all operation fail') !== false) {
                            self::consoleLog('开始执行：3.执行失败，更新 updated_active_time');
                            SuiteCorpLicenseActiveInfoService::updateActiveTimeByCorpAndUserId(['corp_id' => $config['corp_id'], 'userid' => $active['userid']]);
                        }
                    }
                    continue;
                }

                // 3.查询 存在互通账号激活码（状态：已绑定且有效） && 最新激活绑定用户的时间>30天 && 未开启会话存档 && 开启自动授权 && 企微账号状态(已激活OR已禁用)并未删除
                $active = SuiteCorpLicenseActiveInfo::find()
                                                    ->andWhere([
                                                        'suite_id'     => $config['suite_id'],
                                                        'corp_id'      => $config['corp_id'],
                                                        'status'       => SuiteCorpLicenseActiveInfo::STATUS_2,
                                                        'type'         => SuiteCorpLicenseActiveInfo::TYPE_2,
                                                        'is_auto_auth' => SuiteCorpLicenseActiveInfo::ACTIVE_IS_AUTO_AUTH_1
                                                    ])
                                                    ->andWhere(['<', "updated_active_time", time() - 2592000])
                                                    ->andWhere(['EXISTS',
                                                        Account::find()
                                                               ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".suite_id=" . Account::tableName() . ".suite_id")
                                                               ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".corp_id=" . Account::tableName() . ".corp_id")
                                                               ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".userid=" . Account::tableName() . ".userid")
                                                               ->andWhere(['IN', Account::tableName() . '.status', [Account::ACCOUNT_STATUS_1, Account::ACCOUNT_STATUS_2]])
                                                               ->andWhere([Account::tableName() . '.deleted_at' => 0])
                                                             // TODO 存档
                                                    ])
                                                    ->limit(1)
                                                    ->asArray()
                                                    ->one();

                if (!empty($active)) {
                    self::consoleLog('开始执行：3.查询 存在互通账号激活码（状态：已绑定且有效）.....，数据：' . json_encode($active, JSON_UNESCAPED_UNICODE));

                    $user = Account::find()->select('id,userid,jnumber,nickname')->andWhere(['suite_id' => $config['suite_id'], 'corp_id' => $config['corp_id'], 'userid' => $active['userid']])->limit(1)->asArray()->one();
                    self::consoleLog('开始执行：3.用户数据：' . json_encode($user, JSON_UNESCAPED_UNICODE));

                    try {
                        $data = SuiteCorpLicenseOrderService::batchTransferLicense([
                            'corpid'        => $config['corp_id'],
                            'transfer_list' => [['handover_userid' => $active['userid'], 'takeover_userid' => $waitItem['userid']]]
                        ]);
                        self::consoleLog($data);
                    } catch (\Exception $exception) {
                        if (strpos($exception->getMessage(), '企微微信请求失败：all operation fail') !== false) {
                            self::consoleLog('开始执行：3.执行失败，更新 updated_active_time');
                            SuiteCorpLicenseActiveInfoService::updateActiveTimeByCorpAndUserId(['corp_id' => $config['corp_id'], 'userid' => $active['userid']]);
                        }
                    }

                    continue;
                }


                // 4 普通账号 激活码 状态：未绑定
                $active = SuiteCorpLicenseActiveInfo::find()
                                                    ->andWhere([
                                                        'suite_id' => $config['suite_id'],
                                                        'corp_id'  => $config['corp_id'],
                                                        'status'   => SuiteCorpLicenseActiveInfo::STATUS_1,
                                                        'type'     => SuiteCorpLicenseActiveInfo::TYPE_1
                                                    ])
                                                    ->limit(1)
                                                    ->asArray()
                                                    ->one();
                if (!empty($active)) {
                    self::consoleLog('开始执行：4 普通账号 激活码 状态：未绑定，数据：' . json_encode($active, JSON_UNESCAPED_UNICODE));

                    $data = SuiteCorpLicenseOrderService::bindActiveAccount(['corpid' => $config['corp_id'], 'userid' => $waitItem['userid'], 'active_code' => $active['active_code']]);

                    self::consoleLog($data);
                    continue;
                }

                // 5.普通账号 激活码 状态：待转移
                $active = SuiteCorpLicenseActiveInfo::find()
                                                    ->andWhere([
                                                        'suite_id' => $config['suite_id'],
                                                        'corp_id'  => $config['corp_id'],
                                                        'status'   => SuiteCorpLicenseActiveInfo::STATUS_4,
                                                        'type'     => SuiteCorpLicenseActiveInfo::TYPE_2
                                                    ])
                                                    ->limit(1)
                                                    ->asArray()
                                                    ->one();
                if (!empty($active)) {
                    self::consoleLog('开始执行：5.普通账号 激活码 状态：待转移，数据：' . json_encode($active, JSON_UNESCAPED_UNICODE));

                    $user = Account::find()->select('id,userid,jnumber,nickname')->andWhere(['suite_id' => $config['suite_id'], 'corp_id' => $config['corp_id'], 'userid' => $active['userid']])->limit(1)->asArray()->one();
                    self::consoleLog('开始执行：5.用户数据：' . json_encode($user, JSON_UNESCAPED_UNICODE));

                    try {
                        $data = SuiteCorpLicenseOrderService::batchTransferLicense([
                            'corpid'        => $config['corp_id'],
                            'transfer_list' => [['handover_userid' => $active['userid'], 'takeover_userid' => $waitItem['userid']]]
                        ]);
                        self::consoleLog($data);
                    } catch (\Exception $exception) {
                        if (strpos($exception->getMessage(), '企微微信请求失败：all operation fail') !== false) {
                            self::consoleLog('开始执行：5.执行失败，更新 updated_active_time');
                            SuiteCorpLicenseActiveInfoService::updateActiveTimeByCorpAndUserId(['corp_id' => $config['corp_id'], 'userid' => $active['userid']]);
                        }
                    }
                    continue;
                }

            }
        }
        return true;
    }

}