<?php

namespace common\services;

use Yii;
use OSS\OssClient;
use OSS\Core\OssException;
use common\errors\Code;
use common\errors\ErrException;

/**
 * 阿里对象存储
 * Class OssService
 * @package common\services
 */
class OssService extends Service
{

    /**
     * @param $params
     * @return string
     * @throws ErrException
     * @throws OssException
     */
    public static function uploadFile($params)
    {
        $accessKeyId     = Yii::$app->params['oss']['accessKeyId'];
        $accessKeySecret = Yii::$app->params['oss']['accessKeySecret'];
        // Endpoint以杭州为例，其它Region请按实际情况填写。
        $endpoint = Yii::$app->params['oss']['endpoint'];
        // 存储空间名称
        $bucket = Yii::$app->params['oss']['bucket'];
        if (empty($params)) {
            throw new ErrException(Code::DATA_ERROR, '请选择上传文件或限制文件大小');
        }
        // 文件名称
        $object = self::uniqueName($params->getExtension());
        // <yourLocalFile>由本地文件路径加文件名包括后缀组成，例如/users/local/myfile.txt
        $filePath = $params->tempName;
        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $resp      = $ossClient->uploadFile($bucket, $object, $filePath);
            if (!empty($resp['info']['url'])) {
                return $object;
            } else {
                throw new ErrException(Code::CALL_EXCEPTION);
            }
        } catch (OssException $e) {
            throw $e;
        }
    }

    /**
     * @param bool $ext
     * @return string
     */
    private static function uniqueName($ext = false)
    {
        $uniqueStr = env('APP_NAME') . '/' . date("Ymd/") . md5(uniqid() . time() . rand(1111, 9999));
        if ($ext) {
            $uniqueStr = $uniqueStr . '.' . $ext;
        }
        return $uniqueStr;
    }

    /**
     * 文件流上传
     * @param $file * 文件流
     * @param $object * OSS文件存储路径
     * @return mixed
     * @throws ErrException
     * @throws OssException
     * @throws \OSS\Http\RequestCore_Exception
     */
    public static function uploadStreamFile($file, $object)
    {
        $accessKeyId     = Yii::$app->params['oss']['accessKeyId'];
        $accessKeySecret = Yii::$app->params['oss']['accessKeySecret'];
        // Endpoint以杭州为例，其它Region请按实际情况填写。
        $endpoint = Yii::$app->params['oss']['interEndpoint'];
        if (empty($endpoint)) {
            $endpoint = Yii::$app->params['oss']['endpoint'];
        }
        // 存储空间名称
        $bucket = Yii::$app->params['oss']['bucket'];
        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $resp      = $ossClient->putObject($bucket, $object, $file);
            Yii::warning($resp);
            if (!empty($resp['info']['url'])) {
                return $object;
            } else {
                throw new ErrException(Code::CALL_EXCEPTION);
            }
        } catch (OssException $e) {
            throw $e;
        }
    }

}
