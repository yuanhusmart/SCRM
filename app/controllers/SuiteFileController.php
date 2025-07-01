<?php

namespace app\controllers;

use AlibabaCloud\Client\AlibabaCloud;
use Carbon\Carbon;
use common\components\AppController;
use common\errors\Code;
use common\models\SuiteFile;
use Illuminate\Support\Arr;
use Yii;

class SuiteFileController extends AppController
{
    /**
     * 文件新增
     * path: /suite-file/create
     */
    public function actionCreate()
    {
        $params = Arr::only($this->params(), [
            'account_id',
            'name',
            'path',
            'ext',
            'size',
            'belong_id',
            'belong_type',
            'tag',
        ]);

        $params = array_merge([
            'account_id' => auth()->accountId(),
            'created_at' => time(),
            'updated_at' => time(),
        ], $params);

        $file             = new SuiteFile();
        $file->attributes = $params;
        $file->save();

        return $this->responseSuccess([
            'file' => $file,
        ]);
    }

    /**
     * 文件删除
     * path: /suite-file/delete
     */
    public function actionDelete()
    {
        $id = $this->input('id');

        $file = SuiteFile::findOne($id);
        if (!$file) {
            return $this->responseError(Code::NOT_EXIST, '附件不存在');
        }

        // 删除在OSS上的文件
        $accessKeyId     = Yii::$app->params["oss"]["accessKeyId"];
        $accessKeySecret = Yii::$app->params["oss"]["accessKeySecret"];
        $bucket          = Yii::$app->params["oss"]["bucket"];
        $region          = Yii::$app->params["oss"]["region"];

        AlibabaCloud::accessKeyClient($accessKeyId, $accessKeySecret)
            ->regionId($region)
            ->asDefaultClient();

        try {
            AlibabaCloud::rpc()
                ->product('Oss')
                ->scheme('http')
                ->version('2013-07-15')
                ->action('DeleteObject')
                ->method('POST')
                ->host('sts.aliyuncs.com') // 使用OSS endpoint
                ->options([
                    'query' => [
                        'Bucket' => $bucket,
                        'Key'    => $file->path,
                    ]
                ])
                ->request();
        } catch (\Exception $e) {
            logger()->error('[File-Delete] 删除阿里云文件失败', [
                'exception' => $e->__toString()
            ]);
        }

        // 删除数据库中记录
        $file->delete();

        return $this->responseSuccess();
    }

    /**
     * 获取OSS临时上传凭证
     * path: /suite-file/sts
     */
    public function actionSts()
    {
        // 参数配置
        $roleArn         = Yii::$app->params['oss']['sts']['roleArn']; // 补全RoleArn
        $roleSessionName = Yii::$app->params['oss']['sts']['roleSessionName']; // 会话名称

        $accessKeyId     = Yii::$app->params['oss']['accessKeyId'];
        $accessKeySecret = Yii::$app->params['oss']['accessKeySecret'];
        $bucket          = Yii::$app->params['oss']['bucket'];
        $region          = Yii::$app->params['oss']['region'];

        //构建阿里云客户端时需要设置AccessKey ID和AccessKey Secret。
        AlibabaCloud::accessKeyClient($accessKeyId, $accessKeySecret)
            ->regionId($region)
            ->asDefaultClient();

        // 调用阿里云STS服务
        $result = AlibabaCloud::rpc()
            ->product('Sts')
            ->scheme('https')
            ->version('2015-04-01')
            ->action('AssumeRole')
            ->method('POST')
            ->host('sts.aliyuncs.com')
            ->options([
                'query' => [
                    'RoleArn'         => $roleArn,
                    'RoleSessionName' => $roleSessionName,
                    'DurationSeconds' => 3600 // 凭证有效期
                ]
            ])
            ->request();

        $data = $result->toArray();

        // 返回STS临时凭证
        return $this->responseSuccess([
            'access_key_id'     => $data['Credentials']['AccessKeyId'],
            'access_key_secret' => $data['Credentials']['AccessKeySecret'],
            'security_token'    => $data['Credentials']['SecurityToken'],
            'expiration'        => $data['Credentials']['Expiration'],
            'region'            => $region,
            'bucket'            => $bucket,
        ]);
    }

    /**
     * 获取上传对象
     * path: /suite-file/get-object
     */
    public function actionGetObject()
    {
        $ext = $this->input('ext');

        $object = sprintf(
            '%s/%s/%s.%s',
            Yii::$app->params['appName'],
            Carbon::now()->format('Y/m/d'),
            uniqid(),
            $ext
        );

        return $this->responseSuccess([
            'object' => $object,
        ]);
    }
}
