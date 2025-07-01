<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpFeFile;

/**
 * Class SuiteCorpFeFileService
 * @package common\services
 */
class SuiteCorpFeFileService extends Service
{

    /**
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     */
    public static function createOrUpdate($params)
    {
        $attributes = self::includeKeys($params, ['file_key', 'file_value']);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        if (empty($attributes['file_key']) || empty($attributes['file_value'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $create = SuiteCorpFeFile::findOne(['file_key' => $attributes['file_key']]);
        if (empty($create)) {
            $create = new SuiteCorpFeFile();
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
    public static function delete($params)
    {
        $fileKey = self::getString($params, 'file_key');
        if (empty($fileKey)) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data = SuiteCorpFeFile::findOne(['file_key' => $fileKey]);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        if (!$data->delete()) {
            throw new ErrException(Code::DATA_ERROR);
        }
        return true;
    }

    /**
     * @param $params
     * @return SuiteCorpFeFile
     * @throws ErrException
     */
    public static function item($params)
    {
        $fileKey = self::getString($params, 'file_key');
        if (empty($fileKey)) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data = SuiteCorpFeFile::findOne(['file_key' => $fileKey]);
        if (empty($data)) {
            throw new ErrException(Code::DATA_ERROR);
        }
        return $data;
    }

}
