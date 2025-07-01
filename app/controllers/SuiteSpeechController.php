<?php

namespace app\controllers;

use app\transformers\SuiteSpeech\IndexTransformer;
use common\components\AppController;
use common\models\concerns\enums\SuiteSpeech\Type;
use common\models\concerns\filters\SuiteSpeech\Filter;
use common\models\SuiteFile;
use common\models\SuiteSpeech;

class SuiteSpeechController extends AppController
{

    /**
     * 话术列表
     * path: /suite-speech/index
     */
    public function actionIndex()
    {
        $paginator = SuiteSpeech::find()
            ->filter(new Filter($this->input()))
            ->paginate($this->input('per_page', 20));

        return $this->responsePaginator($paginator, new IndexTransformer());
    }

    /**
     * 话术详情
     * path: /suite-speech/show
     */
    public function actionShow()
    {
        $id   = $this->input('id');
        $with = $this->input('with');

        $speech = SuiteSpeech::find()
            ->andWhere(['id' => $id])
            ->when($with, function ($query) use ($with) {
                $query->with($with);
            })
            ->one();

        return $this->responseItem($speech);
    }

    /**
     * 话术新增
     * path: /suite-speech/create
     */
    public function actionCreate()
    {
        $data   = $this->only([
            'suite_id',
            'corp_id',
            'category_id',
            'industry_no',
            'name',
            'type',
            'content',
            'status',
            'kind',
        ]);
        $fileId = $this->input('file_id');

        $data = array_merge([
            'suite_id'    => auth()->suiteId(),
            'corp_id'     => auth()->config()['corp_id'],
            'status'      => 1,
            'operator_id' => auth()->accountId(),
            'creator_id'  => auth()->accountId(),
            'created_at'  => time(),
            'updated_at'  => time(),
            'kind'        => 1
        ], $data);

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $speech = new SuiteSpeech();
            $speech->setAttributes($data, false);
            $speech->save();

            if ($speech->type == Type::ATTACHMENT && $fileId) {
                SuiteFile::updateAll([
                    'belong_id'   => $speech->id,
                    'belong_type' => SuiteSpeech::class,
                ], ['id' => $fileId]);
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            return $this->responseThrow($e);
        }

        return $this->responseSuccess();
    }

    /**
     * 话术编辑
     * path: /suite-speech/update
     */
    public function actionUpdate()
    {
        $id = $this->input('id');

        $data = $this->only([
            'category_id',
            'industry_no',
            'name',
            'type',
            'content',
            'status',
        ]);

        $fileId = $this->input('file_id');

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $speech = SuiteSpeech::findOne($id);

            $speech->setAttributes($data, false);
            $speech->operator_id = auth()->accountId();
            $speech->updated_at  = time();
            $speech->save();

            $oldFileId = $speech->file->id ?? 0;
            if ($speech->type == Type::ATTACHMENT && $fileId != $oldFileId) {
                SuiteFile::updateAll([
                    'belong_id'   => $speech->id,
                    'belong_type' => SuiteSpeech::class,
                ], ['id' => $fileId]);
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            return $this->responseThrow($e);
        }

        return $this->responseSuccess();
    }

    /**
     * 话术删除
     * path: /suite-speech/delete
     */
    public function actionDelete()
    {
        $id = $this->input('id');

        $speech = SuiteSpeech::findOne($id);
        $speech->delete();

        return $this->responseSuccess();
    }
}

