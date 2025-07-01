<?php

namespace app\controllers;

use app\validators\SuiteSpeechCategory\CreateValidator;
use app\validators\SuiteSpeechCategory\UpdateValidator;
use common\components\AppController;
use common\errors\Code;
use common\helpers\Tree;
use common\models\SuiteCorpAccountsDepartment;
use common\models\SuiteCorpDepartment;
use common\models\SuiteSpeechCategory;
use common\models\SuiteSpeechCategoryDepartment;

class SuiteSpeechCategoryController extends AppController
{

    /**
     * 话术分类树
     * path: /suite-speech-category/tree
     */
    public function actionTree()
    {
        $suiteId = $this->input('suite_id', auth()->suiteId());
        $corpId  = $this->input('corp_id', auth()->config()['corp_id']);
        $mine    = $this->input('mine', 0);

        $categories = SuiteSpeechCategory::find()
            ->where(['suite_id' => $suiteId, 'corp_id' => $corpId, 'deleted_at' => 0])
            ->when($mine, function ($query, $mine) use ($suiteId, $corpId) {

                // 只查询设置了课件范围的数据
                $userid = auth()->account()['userid'];

                $departmentId = SuiteCorpAccountsDepartment::find()
                    ->select(['department_id'])
                    ->andWhere([
                        'suite_id' => $suiteId,
                        'corp_id'  => $corpId,
                        'userid'   => $userid
                    ])
                    ->column();

                if (!$departmentId) {
                    $query->andWhere(['id' => 0]);
                    return;
                }

                $path = SuiteCorpDepartment::find()
                    ->select(['path'])
                    ->andWhere([
                        'suite_id'      => $suiteId,
                        'corp_id'       => $corpId,
                        'department_id' => $departmentId
                    ])
                    ->scalar();

                $paths = SuiteCorpDepartment::find()
                    ->select(['path'])
                    ->orWhere(['path' => $path])
                    ->orWhere(['like', 'path', $path . '-%', false])
                    ->column();


                if (!$paths) {
                    $query->andWhere(['id' => 0]);
                    return;
                }

                $ids = array_reduce($paths, function ($carry, $item) {
                    $arr = explode($item, '-');

                    foreach ($arr as $id) {
                        $carry[$id] = 1;
                    }

                    return $carry;
                }, []);

                $ids = array_keys($ids);

                $query->andWhere(['id' => $ids]);
            })
            ->asArray()
            ->all();

        $tree = Tree::make()->toTree($categories);

        return $this->responseSuccess([
            'tree' => $tree,
        ]);
    }

    /**
     * 话术分类详情
     * path: /suite-speech-category/show
     */
    public function actionShow()
    {
        $id   = $this->input('id');
        $with = $this->input('with');

        $category = SuiteSpeechCategory::find()
            ->andWhere(['id' => $id])
            ->when($with, function ($query, $with) {
                $query->with((array)$with);
            })
            ->one();

        return $this->responseItem($category);
    }

    /**
     * 话术分类添加
     * path: /suite-speech-category/create
     */
    public function actionCreate()
    {
        CreateValidator::validateData($this->input());

        $suiteId     = $this->input('suite_id', auth()->suiteId());
        $corpId      = $this->input('corp_id', auth()->config()['corp_id']);
        $name        = $this->input('name');
        $parentId    = $this->input('parent_id', 0);
        $departments = $this->input('departments');

        $transtion = \Yii::$app->db->beginTransaction();
        try {
            $parent = SuiteSpeechCategory::findOne($parentId);

            $category             = new SuiteSpeechCategory();
            $category->suite_id   = $suiteId;
            $category->corp_id    = $corpId;
            $category->name       = $name;
            $category->created_at = time();
            $category->updated_at = time();
            $category->save();

            if ($parent) {
                $category->parent_id = $parentId;
                $category->path      = $parent->path . '-' . $category->id;
                $category->path_name = $parent->path_name . '-' . $category->name;
            } else {
                $category->path      = $category->id;
                $category->path_name = $category->name;
            }

            $category->save();

            if ($departments) {
                SuiteSpeechCategoryDepartment::batchInsert(array_map(function ($department) use ($category) {
                    return [
                        'category_id'   => $category->id,
                        'department_id' => $department['department_id'],
                        'path'          => $department['path'],
                    ];
                }, $departments));
            }

            $transtion->commit();
        } catch (\Throwable $e) {
            $transtion->rollBack();

            return $this->responseThrow($e);
        }

        return $this->responseSuccess();
    }

    /**
     * 话术分类编辑
     * path: /suite-speech-category/update
     */
    public function actionUpdate()
    {
        UpdateValidator::validateData($this->input());

        $id          = $this->input('id');
        $name        = $this->input('name');
        $parentId    = $this->input('parent_id', 0);
        $departments = $this->input('departments');

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $parent = SuiteSpeechCategory::findOne($parentId);

            $category       = SuiteSpeechCategory::findOne($id);
            $category->name = $name;

            if ($parent) {
                $category->parent_id = $parentId;
                $category->path      = $parent->path . '-' . $category->id;
                $category->path_name = $parent->path_name . '-' . $category->name;
            } else {
                $category->path      = $category->id;
                $category->path_name = $category->name;
            }

            $category->save();


            SuiteSpeechCategoryDepartment::deleteAll(['category_id' => $id]);
            if ($departments) {
                SuiteSpeechCategoryDepartment::batchInsert(array_map(function ($department) use ($category) {
                    return [
                        'category_id'   => $category->id,
                        'department_id' => $department['department_id'],
                        'path'          => $department['path'],
                    ];
                }, $departments));
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            return $this->responseThrow($e);
        }


        return $this->responseSuccess();
    }

    /**
     * 话术分类删除
     * path: /suite-speech-category/delete
     */
    public function actionDelete()
    {
        $id = $this->input('id');

        $category = SuiteSpeechCategory::findOne($id);

        if (!$category) {
            return $this->responseError(Code::PARAMS_ERROR, '未找到分类');
        }

        $category->deleted_at = time();
        $category->save();

        return $this->responseSuccess();
    }
}