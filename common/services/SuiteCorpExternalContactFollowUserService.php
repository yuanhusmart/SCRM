<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpExternalContact;
use common\models\SuiteCorpExternalContactFollowUser;

/**
 * Class SuiteCorpExternalContactFollowUserService
 * @package common\services
 */
class SuiteCorpExternalContactFollowUserService extends Service
{

    const CHANGE_FIELDS = ['external_contact_id', 'userid', 'remark', 'description', 'createtime', 'remark_corp_name', 'add_way', 'wechat_channels_nickname', 'wechat_channels_source', 'oper_userid', 'state', 'updated_at', 'deleted_at'];

    /**
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     */
    public static function create($params)
    {
        $attributes = self::includeKeys($params, self::CHANGE_FIELDS);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $externalContactId = $attributes['external_contact_id'] ?? 0;
        $userid            = $attributes['userid'] ?? '';
        $create            = SuiteCorpExternalContactFollowUser::findOne(['external_contact_id' => $externalContactId, 'userid' => $userid]);
        if (empty($create)) {
            $create = new SuiteCorpExternalContactFollowUser();
        }
        $create->load($attributes, '');
        //校验参数
        if (!$create->validate()) {
            throw new ErrException(Code::PARAMS_ERROR, $create->getError());
        }
        if (!$create->save()) {
            throw new ErrException(Code::CREATE_ERROR, $create->getError());
        }
        return $create->getPrimaryKey();
    }

    /**
     * @param $params
     * @return true
     * @throws ErrException
     */
    public static function update($params)
    {
        $id = self::getId($params);
        if (!$id) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data = SuiteCorpExternalContactFollowUser::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        $attributes = self::includeKeys($params, self::CHANGE_FIELDS);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data->attributes = $attributes;
        if (!$data->save()) {
            throw new ErrException(Code::UPDATE_ERROR, $data->getError());
        }
        return true;
    }

    /**
     * @param $params
     * @return true
     * @throws ErrException
     */
    public static function delete($params)
    {
        $externalUserid    = $params['ExternalUserID'] ?? '';
        $corpId            = $params['AuthCorpId'] ?? '';
        $suiteId           = $params['SuiteId'] ?? '';
        $userid            = $params['UserID'] ?? '';
        $deletedAt         = $params['TimeStamp'] ?? time();
        $externalContactId = SuiteCorpExternalContact::find()->where(['suite_id' => $suiteId, 'corp_id' => $corpId, 'external_userid' => $externalUserid])->select('id')->scalar();
        if (empty($externalContactId)) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data = SuiteCorpExternalContactFollowUser::findOne(['external_contact_id' => $externalContactId, 'userid' => $userid]);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        $data->deleted_at = $deletedAt;
        if (!$data->save()) {
            throw new ErrException(Code::DATA_ERROR);
        }
        return true;
    }

    /**
     * @param $externalContactId
     * @return int
     * @throws \yii\base\InvalidConfigException
     */
    public static function deleteAll($externalContactId)
    {
        return SuiteCorpExternalContactFollowUser::updateAll(['deleted_at' => time()], ['external_contact_id' => $externalContactId]);
    }

}
