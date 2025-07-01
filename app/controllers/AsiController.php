<?php

namespace app\controllers;

use common\components\BaseController;
use common\errors\Code;
use common\errors\ErrException;
use common\models\Account;
use common\models\SuiteCorpConfig;
use common\services\Service;
use common\services\SuiteService;

/**
 * Class AsiController
 * @package app\controllers
 */
class AsiController extends BaseController
{

    /**
     * path: /asi/message-send
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionMessageSend()
    {
        $params = $this->getBodyParams();
        if (empty($params['suite_id']) || empty($params['corp_id']) || empty($params['userid'])) {
            return $this->responseError(Code::PARAMS_ERROR, '参数值错误');
        }

        $config = SuiteCorpConfig::findOne(['suite_id' => $params['suite_id'], 'corp_id' => $params['corp_id']]);
        if (empty($config)) {
            return $this->responseError(Code::PARAMS_ERROR, '业务应用不存在');
        }
        $resp = SuiteService::messageSend($config->suite_id, $config->corp_id, [
            "touser"                   => $params['userid'],
            "msgtype"                  => "text",
            "agentid"                  => $config->suite_agent_id,
            "text"                     => ["content" => $params['content']],
            "safe"                     => 0,
            "enable_id_trans"          => 0,
            "enable_duplicate_check"   => 0,
            "duplicate_check_interval" => 1800
        ]);
        return $this->responseSuccess($resp);
    }

    /**
     * 通过userid获取企业已配置的「联系我」方式
     * path: /asi/get-contact-way-by-number
     * @return \yii\web\Response
     * @throws ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionGetContactWayByNumber()
    {
        $params  = $this->getBodyParams();
        $userid  = Service::getInt($params, 'userid');
        $suiteId = Service::getString($params, 'suite_id');
        $corpId  = Service::getString($params, 'corp_id');
        $config  = SuiteCorpConfig::findOne(['suite_id' => $suiteId, 'corp_id' => $corpId]);
        if (empty($config)) {
            throw new ErrException(Code::PARAMS_ERROR, '业务应用不存在');
        }
        $accounts = Account::find()
                           ->select(['id', 'userid', 'suite_id', 'corp_id', 'contact_way_config_id AS config_id', 'qr_code'])
                           ->andWhere(['suite_id' => $config->suite_id])
                           ->andWhere(['corp_id' => $config->corp_id])
                           ->andWhere(['userid' => $userid])
                           ->andWhere(['status' => Account::ACCOUNT_STATUS_1])
                           ->andWhere(['deleted_at' => 0])
                           ->asArray()
                           ->all();
        if (empty($accounts)) {
            throw new ErrException(Code::PARAMS_ERROR, '用户未找到');
        }
        foreach ($accounts as &$item) {
            if (empty($item['qr_code'])) {
                $resp = SuiteService::getExternalContactAddContactWay($config->suite_id, $config->config_id, $item['userid']);
                if (!empty($resp['qr_code'])) {
                    Account::updateAll(['contact_way_config_id' => $resp['config_id'], 'qr_code' => $resp['qr_code']], ['id' => $item['id']]);
                    $item['config_id'] = $resp['config_id'];
                    $item['qr_code']   = $resp['qr_code'];
                }
            }
        }
        return $this->responseSuccess($accounts);
    }

    /**
     * 通过userid获取员工记录ID
     * path: /asi/get-account-id
     * @return \yii\web\Response
     */
    public function actionGetAccountId()
    {
        $params  = $this->getBodyParams();
        $userid  = Service::getString($params, 'userid');
        $account = Account::find()->select(['id'])->andWhere(['suite_id' => auth()->suiteId(), 'corp_id' => auth()->corpId(), 'userid' => $userid])->one();
        return $this->responseSuccess(['id' => $account ? $account->id : 0]);
    }
}
