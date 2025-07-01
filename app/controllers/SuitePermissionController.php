<?php

namespace app\controllers;

use app\events\SuitePermission\Update;
use app\validators\SuitePermission\CreateValidator;
use app\validators\SuitePermission\DeleteValidator;
use Codeception\Suite;
use common\components\AppController;
use common\components\BaseController;
use common\helpers\Tree;
use common\models\concerns\filters\SuitePermission\Filter;
use common\models\SuitePermission;

class SuitePermissionController extends BaseController
{

    /**
     * 权限列表
     * path: /suite-permission/index
     */
    public function actionIndex()
    {
        $paginator = SuitePermission::find()
            ->filter(new Filter($this->input()))
            ->paginate($this->input('per_page'));

        return $this->responsePaginator($paginator);
    }

    /**
     * 获取权限树
     * path: /suite-permission/tree
     */
    public function actionTree()
    {
        $with = $this->input('with');

        $list = SuitePermission::find()
            ->when($with, function ($query, $with) {
                return $query->with($with);
            })
            ->asArray()
            ->all();


        $tree = Tree::make()->toTree($list);

        return $this->responseSuccess([
            'tree' => $tree
        ]);
    }

    /**
     * 权限新增
     * path: /suite-permission/create
     */
    public function actionCreate()
    {
        CreateValidator::validateData($this->input());

        $data = $this->only([
            'type',
            'parent_id',
            'name',
            'slug',
            'route',
            'level',
            'is_hide',
            'status',
        ]);

        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $permission = new SuitePermission();
            $permission->setAttributes($data, false);
            $permission->created_at = time();
            $permission->updated_at = time();
            $permission->creator_id = auth()->account()['id'];
            $permission->save();

            if ($parentId = $this->input('parent_id')) {
                $parent                = SuitePermission::findOne($parentId);
                $permission->path      = $parent->path . '-' . $permission->id;
                $permission->path_name = $parent->path_name . '-' . $permission->name;
            } else {
                $permission->path      = $permission->id;
                $permission->path_name = $permission->name;
            }

            $permission->save();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->commit();

            return $this->responseThrow($e);
        }

        return $this->responseSuccess();
    }

    /**
     * 权限修改
     * path: /suite-permission/update
     */
    public function actionUpdate()
    {
        $id   = $this->input('id');
        $data = $this->only([
            'type',
            //            'parent_id',
            'name',
            'slug',
            'route',
            'level',
            'is_hide',
            'status',
        ]);

        $permission = SuitePermission::findOne($id);
        $original   = clone $permission;

        if ($parentId = $this->input('parent_id')) {
            $parent                = SuitePermission::findOne($parentId);
            $permission->path      = $parent->path . '-' . $permission->id;
            $permission->path_name = $parent->path_name . '-' . $permission->name;
        } else {
            $permission->path      = $permission->id;
            $permission->path_name = $permission->name;
        }

        $permission->setAttributes($data, false);
        $permission->updated_at = time();
        $permission->save();

        Update::make()
            ->permission($permission)
            ->original($original)
            ->data($data)
            ->fire();

        return $this->responseSuccess();
    }

    /**
     * 权限删除
     * path: /suite-permission/delete
     */
    public function actionDelete()
    {
        DeleteValidator::validateData($this->input());

        $id = $this->input('id');

        $permission = SuitePermission::findOne($id);
        $permission->delete();

        return $this->responseSuccess();
    }
}