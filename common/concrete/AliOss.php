<?php

namespace common\concrete;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use common\errors\Code;
use common\errors\ErrException;
use common\helpers\ArrayHelper;
use OSS\Core\OssException;
use OSS\Model\CorsConfig;
use OSS\Model\CorsRule;
use OSS\OssClient;
use yii\web\UploadedFile;

/**
 * Class AliOss
 * @package App\Common\Tool
 */
class AliOss
{
    private $accessKeyId;
    private $accessKeySecret;
    private $endpoint;
    private $bucket;

    public function __construct()
    {
        $this->accessKeyId     = env('OSS_ACCESS_KEY_ID');
        $this->accessKeySecret = env('OSS_ACCESS_KEY_SECRET');
        $this->endpoint        = env('OSS_ENDPOINT');
        $this->bucket          = env('OSS_BUCKET');
    }

    /**
     * 获取obj
     * @param $object
     * @return string
     * @throws ErrException
     * @throws OssException
     */
    public function getObject($object)
    {
        $accessKeyId     = $this->accessKeyId;
        $accessKeySecret = $this->accessKeySecret;
        $endpoint        = $this->endpoint;
        $ossClient       = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $bucket          = $this->bucket;
        //$bucket          = 'business-media';
        try {
            $data = $ossClient->getObject($bucket, $object);
            return $data;
        } catch (OssException $e) {
            throw new ErrException(Code::DATA_ERROR, $e->getMessage());
        }
    }

    /**
     * 上传obj
     * @param $object
     * @param $content
     * @return mixed
     * @throws ErrException
     * @throws OssException
     */
    public function putObject($object, $content)
    {
        $accessKeyId     = $this->accessKeyId;
        $accessKeySecret = $this->accessKeySecret;
        $endpoint        = $this->endpoint;
        $ossClient       = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $bucket          = $this->bucket;
        try {
            $ossClient->putObject($bucket, $object, $content);
            return $object;
        } catch (OssException $e) {
            throw new ErrException(Code::DATA_ERROR, $e->getMessage());
        }
    }

    /**
     *  跨域资源上传obj
     * @param $object
     * @param $content
     * @return mixed
     * @throws ErrException
     * @throws OssException
     */
    public function putObjectCors($object, $content)
    {
        $accessKeyId     = $this->accessKeyId;
        $accessKeySecret = $this->accessKeySecret;
        $endpoint        = $this->endpoint;
        $ossClient       = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $bucket          = $this->bucket;
        $corsConfig      = new CorsConfig();
        $rule            = new CorsRule();
        $rule->addAllowedHeader("*");
        $rule->addAllowedOrigin('*');
        $rule->addAllowedMethod("POST");
        $rule->setMaxAgeSeconds(10);
        $corsConfig->addRule($rule);
        try {
            $ossClient->putObject($bucket, $object, $content);
            return $object;
        } catch (OssException $e) {
            throw new ErrException(Code::DATA_ERROR, $e->getMessage());
        }
    }

    /**
     * 跨域资源获取obj
     * @param $object
     * @return string
     * @throws ErrException
     * @throws OssException
     */
    public function getObjectCors($object)
    {
        $accessKeyId     = $this->accessKeyId;
        $accessKeySecret = $this->accessKeySecret;
        $endpoint        = $this->endpoint;
        $ossClient       = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
        $bucket          = $this->bucket;
        try {
            $data = $ossClient->getObject($bucket, $object);
            return $data;
        } catch (OssException $e) {
            throw new ErrException(Code::DATA_ERROR, $e->getMessage());
        }
    }

    /**
     * @param UploadedFile $fileObj
     * @return string
     * @throws ErrException
     */
    public function upload(UploadedFile $fileObj, $savePath): string
    {
        $file_path = $fileObj->tempName;
        if (empty($savePath)) {
            throw new ErrException(Code::PARAMS_ERROR, '参数错误');
        }
        try {
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
            $ossClient->uploadFile($this->bucket, $savePath, $file_path);

            return $savePath;
        } catch (OssException $e) {
            throw new ErrException(Code::CODE_SEND_ERROR, $e->getMessage());
        }
    }

    /**
     * @param UploadedFile $fileObj
     * @return string
     * @throws ErrException
     */
    public function userCenterUpload(UploadedFile $fileObj, $savePath): string
    {
        $file_path = $fileObj->tempName;
        if (empty($savePath)) {
            throw new ErrException(Code::BUSINESS_UNABLE_PROCESS, '参数错误');
        }
        try {
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
            $ossClient->uploadFile($this->bucket, $savePath, $file_path);

            return $savePath;
        } catch (OssException $e) {
            throw new ErrException(Code::BUSINESS_UNABLE_PROCESS, $e->getMessage());
        }
    }

    /**
     * 上传服务器本地文件至OSS
     * @param $savePath
     * @param $filePath
     * @return bool
     * @throws ErrException
     */
    public function uploadFile($savePath, $filePath)
    {
        try {
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
            $ossClient->uploadFile($this->bucket, $savePath, $filePath);

            return true;
        } catch (OssException $e) {
            throw new ErrException(Code::CODE_SEND_ERROR, $e->getMessage());
        }
    }

    /**
     * @param string $file_path
     * @return string
     * @throws ErrException
     */
    public function getUrl(string $file_path): string
    {
        // 设置URL的有效时长为300s。
        $timeout = 300;
        try {
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint, false);
            $bucket    = $this->bucket;
            $object    = $file_path;
            // 生成GetObject的签名URL。
            $signedUrl = $ossClient->signUrl($bucket, $object, $timeout);
            return $signedUrl;
        } catch (OssException $e) {
            throw new ErrException(Code::CODE_SEND_ERROR, $e->getMessage());
        }
    }

    /**
     * @param string $file_path
     * @return string
     * @throws ErrException
     */
    public function getPayCenterUrl(string $file_path): string
    {
        // 设置URL的有效时长为300s。
        $accessKeyId     = env('OSS_ACCESS_KEY_ID_PAY_CENTER');
        $accessKeySecret = env('OSS_ACCESS_KEY_SECRET_PAY_CENTER');
        $endpoint        = env('OSS_ENDPOINT_PAY_CENTER');
        $bucket          = env('OSS_BUCKET_PAY_CENTER');
        $timeout         = 300;
        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint, false);
            $object    = $file_path;
            // 生成GetObject的签名URL。
            $signedUrl = $ossClient->signUrl($bucket, $object, $timeout);
            return $signedUrl;
        } catch (OssException $e) {
            throw new ErrException(Code::CODE_SEND_ERROR, $e->getMessage());
        }
    }

    /**
     * @param  $fileObj
     * @return string
     */
    public function createSaveFileName(UploadedFile $fileObj, $dir = '')
    {
        $file_ext = $fileObj->getExtension();
        return $dir . '/' . date("YmdHis") . mt_rand(111, 999) . '_' . md5(uniqid()) . '.' . $file_ext;
    }

    /**
     * 获取OSS临时上传令牌
     * date 21.3.23
     */
    public function getTempOssSts()
    {

        try {
            $accessKeyId     = env('OSS_STS_ACCESS_KEY_ID');
            $accessKeySecret = env('OSS_STS_ACCESS_KEY_SECRET');
            $endpoint        = env('OSS_STS_ENDPOINT');
            $bucket          = env('OSS_STS_BUCKET');

            $regionId        = env('OSS_STS_REGIONID');
            $roleArn         = env('OSS_STS_ROLEARN');
            $roleSessionName = env('OSS_STS_ROLESESSIONNAME');

            //构建阿里云客户端时需要设置AccessKey ID和AccessKey Secret。
            AlibabaCloud::accessKeyClient($accessKeyId, $accessKeySecret)
                        ->regionId($regionId)
                        ->asDefaultClient();
            //设置参数，发起请求。
            $result = AlibabaCloud::rpc()->product('Sts')
                                  ->scheme('https') // https | http
                                  ->version('2015-04-01')
                                  ->action('AssumeRole')
                                  ->method('POST')
                                  ->host('sts.aliyuncs.com')
                                  ->options([
                                      'query' => [
                                          'RegionId'        => $regionId,       #"cn-shenzhen",
                                          'RoleArn'         => $roleArn,        #"acs:ram::1726155794450775:role/oss-yuzhua-mats-sim",
                                          'RoleSessionName' => $roleSessionName,#"oss-yuzhua-mats-sim",
                                      ],
                                  ])
                                  ->request()->toArray();

            $securityToken   = $result['Credentials']['SecurityToken'];
            $accessKeyId     = $result['Credentials']['AccessKeyId'];
            $accessKeySecret = $result['Credentials']['AccessKeySecret'];

            // $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint, false, $securityToken);
            // // 使用STS临时授权上传文件。
            // $result         = $ossClient->uploadFile($bucket, $this->savepath. '人才改制权限.xlsx', $this->body);
        } catch (ClientException $e) {
            throw $e;
        } catch (ServerException $e) {
            throw $e;
        }

        $saveDir = 'data-center/finance/' . date("Y") . '/' . date("m");

        $expiration = ArrayHelper::getValue($result, 'Credentials.Expiration', 0);
        $expiration = $expiration ? strtotime($expiration) : 0;

        return [
            'AccessKeyId'     => $result['Credentials']['AccessKeyId'],
            'AccessKeySecret' => $result['Credentials']['AccessKeySecret'],
            'securityToken'   => $result['Credentials']['SecurityToken'],
            'region'          => 'cn-shenzhen',
            'bucket'          => $bucket,
            'endpoint'        => $endpoint,
            'dir'             => '/' . $saveDir,
            'expiration'      => $expiration
        ];
    }
}
