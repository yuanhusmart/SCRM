<?php

namespace app\controllers;

use app\transformers\SuiteRole\IndexTransformer;
use app\validators\SuiteRole\CreateValidator;
use app\validators\SuiteRole\UpdateValidator;
use Codeception\Suite;
use common\components\BaseController;
use common\errors\Code;
use common\models\concerns\filters\SuiteRole\Filter;
use common\models\SuiteRole;
use common\models\SuiteRolePermission;

class SuiteRoleController extends BaseController
{

    /**
     * 角色列表
     * path: /suite-role/index
     */
    public function actionIndex()
    {
        $paginator = SuiteRole::find()
            ->filter(new Filter($this->input()))
            ->paginate($this->input('per_page', 20));

        return $this->responsePaginator($paginator, new IndexTransformer());
    }

    /**
     * 角色新增
     * path: /suite-role/create
     */
    public function actionCreate()
    {
        CreateValidator::validateData($this->input());

        $name        = $this->input('name');
        $description = $this->input('description');
        $corpId      = $this->input('corp_id', auth()->config()['corp_id']);
        $kind        = $this->input('kind', 1);
        $suiteId     = $this->input('suite_id', auth()->config()['suite_id']);

        $role              = new SuiteRole();
        $role->suite_id    = $suiteId;
        $role->corp_id     = $corpId;
        $role->name        = $name;
        $role->description = (string)$description;
        $role->kind        = $kind;
        $role->created_at  = time();
        $role->updated_at  = time();
        $role->save();

        return $this->responseSuccess();
    }

    /**
     * 角色修改
     * path: /suite-role/update
     */
    public function actionUpdate()
    {
        UpdateValidator::validateData($this->input());

        $id          = $this->input('id');
        $name        = $this->input('name');
        $description = $this->input('description');

        $role              = SuiteRole::findOne($id);
        $role->name        = $name;
        $role->description = (string)$description;
        $role->updated_at  = time();
        $role->save();

        return $this->responseSuccess();
    }

    /**
     * 角色删除
     * path: /suite-role/delete
     */
    public function actionDelete()
    {
        $id = $this->input('id');

        $role             = SuiteRole::findOne($id);
        $role->deleted_at = time();
        $role->save();

        return $this->responseSuccess();
    }

    /**
     * 角色默认设置
     * path: /suite-role/set-default
     */
    public function actionSetDefault()
    {
        $id        = $this->input('id');
        $isDefault = $this->input('is_default');

        $role = SuiteRole::findOne($id);

        if (!$role) {
            return $this->responseError(Code::PARAMS_ERROR, '角色不存在');
        }

        $role->is_default = $isDefault;
        $role->updated_at = time();
        $role->save();

        // 需要将其他的数据改为否
        if ($isDefault == YES) {
            SuiteRole::updateAll(['is_default' => NO], [
                'AND',
                ['!=', 'id', $id],
                ['corp_id' => $role->corp_id]
            ]);
        }

        return $this->responseSuccess();
    }

    /**
     * 角色设置权限
     * path: /suite-role/set-permission
     */
    public function actionSetPermission()
    {
        $id            = $this->input('id');
        $permissionIds = $this->input('permission_id');

        SuiteRolePermission::deleteAll(['role_id' => $id]);

        $insert = array_map(function ($permissionId) use ($id) {
            return [
                'role_id'       => $id,
                'permission_id' => $permissionId,
            ];
        }, (array)$permissionIds);

        SuiteRolePermission::batchInsert($insert);

        return $this->responseSuccess();
    }

    /**
     * 获取角色权限
     * path: /suite-role/get-permission
     */
    public function actionGetPermission()
    {
        $id = $this->input('id');

        $role = SuiteRole::findOne($id);

        if (!$role) {
            return $this->responseError(Code::PARAMS_ERROR, '角色不存在');
        }

        $permissionIds = SuiteRolePermission::find()
            ->where(['role_id' => $id])
            ->select('permission_id')
            ->column();

        return $this->responseSuccess([
            'permission_id' => $permissionIds,
        ]);
    }
}

