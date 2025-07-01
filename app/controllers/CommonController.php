<?php

namespace app\controllers;

use common\components\BaseController;
use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpConfig;
use common\services\EnumService;
use common\services\SuiteProgramService;
use common\services\SuiteService;

class CommonController extends BaseController
{

    /**
     * @return \yii\web\Response
     */
    public function actionEnum()
    {
        return $this->responseSuccess(EnumService::get());
    }

    /**
     * 上传临时素到专区
     * @return \yii\web\Response
     * @throws ErrException
     */
    public function actionUploadMedia()
    {
        $params = $this->getQueryParams();
        if (empty($params['suite_id']) || empty($params['corp_id'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        if (empty($_FILES["media"])) {
            throw new ErrException(Code::PARAMS_ERROR, '请上传文件');
        }

        $error = $_FILES['media']['error'] ?? 0;

        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return $this->responseError(Code::PARAMS_ERROR, '文件过大');
            case UPLOAD_ERR_PARTIAL:
                return $this->responseError(Code::PARAMS_ERROR, '文件只有部分被上传');
            case UPLOAD_ERR_NO_FILE:
                return $this->responseError(Code::PARAMS_ERROR, '没有文件被上传');
            case UPLOAD_ERR_NO_TMP_DIR:
                return $this->responseError(Code::PARAMS_ERROR, '找不到临时文件夹');
            case UPLOAD_ERR_CANT_WRITE:
                return $this->responseError(Code::PARAMS_ERROR, '文件写入失败');
        }


        $config = SuiteCorpConfig::find()->andWhere(['suite_id' => $params['suite_id'], 'corp_id' => $params['corp_id']])->asArray()->limit(1)->one();
        if (empty($config)) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        // 获取文件扩展名
        $data = SuiteProgramService::asyncUploadMedia($config['suite_id'], $config['corp_id'], 'file', $_FILES["media"]);

        $data = json_decode($data, true);
        if ($data['errcode'] != 0) {
            logger()->error('[上传临时素材]', (array)$data);
            return $this->responseError(Code::PARAMS_ERROR, '企微上传失败');
        }

        return $this->responseSuccess($data);
    }


    /**
     * 上传临时文件
     * path: /common/upload-temp-file
     */
    public function actionUploadTempFile()
    {
        $params = $this->getQueryParams();
        if (empty($params['suite_id']) || empty($params['corp_id'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        if (empty($_FILES["media"])) {
            throw new ErrException(Code::PARAMS_ERROR, '请上传文件');
        }

        $config = SuiteCorpConfig::find()->andWhere(['suite_id' => $params['suite_id'], 'corp_id' => $params['corp_id']])->asArray()->limit(1)->one();
        if (empty($config)) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        // 获取文件扩展名
        $data = SuiteService::uploadMedia($config['suite_id'], $config['corp_id'], $params['type'], $_FILES["media"]);

        $data = json_decode($data, true);

        if ($data['errcode'] != 0) {
            logger()->error('[上传临时素材]', (array)$data);
            return $this->responseError(Code::PARAMS_ERROR, '企微上传失败');
        }

        return $this->responseSuccess($data);
    }
}