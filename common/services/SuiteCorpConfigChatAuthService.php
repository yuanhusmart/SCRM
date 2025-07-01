<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpConfigChatAuth;

/**
 * Class SuiteCorpConfigChatAuthService
 * @package common\services
 */
class SuiteCorpConfigChatAuthService extends Service
{

    // 企业微信 应用授权存档的成员-权限变更 广播 交换机
    const MQ_FAN_OUT_EXCHANGE_CORP_CHANGE_AUTH = 'aaw.fan.out.corp.change.auth.dir.ex';
    // 企业微信 应用授权存档的成员-权限变更 广播 routingKey
    const MQ_FAN_OUT_ROUTING_KEY_CORP_CHANGE_AUTH = 'aaw.fan.out.corp.change.auth.rk';

    // 企业微信 应用授权存档的成员-权限变更 广播 队列
    const MQ_CORP_CHANGE_AUTH_QUEUE = 'aaw.fan.out.corp.change.auth.que';

    // 企业微信 应用管理员-权限变更 广播 队列
    const MQ_CORP_ADMIN_LIST_QUEUE = 'aaw.fan.out.corp.admin.list.que';

    /**
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     */
    public static function create($params)
    {
        $attributes = self::includeKeys($params, SuiteCorpConfigChatAuth::CHANGE_FIELDS);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $create = SuiteCorpConfigChatAuth::find()->where(['config_id' => $params['config_id'], 'userid' => $params['userid'], 'edition' => $params['edition']])->one();
        if (empty($create)) {
            $create = new SuiteCorpConfigChatAuth();
        }
        $create->load($attributes, '');
        //校验参数
        if (!$create->validate()) {
            throw new ErrException(Code::PARAMS_ERROR, $create->getErrors());
        }
        if (!$create->save()) {
            throw new ErrException(Code::CREATE_ERROR, $create->getErrors());
        }
        return $create->getPrimaryKey();
    }

    /**
     * @param $params
     * @return true
     * @throws ErrException
     */
    public static function delete($params)
    {
        $id = self::getId($params);
        if (empty($id)) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data = SuiteCorpConfigChatAuth::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        $data->deleted_at = time();
        if (!$data->save()) {
            throw new ErrException(Code::DATA_ERROR);
        }
        return true;
    }

}
