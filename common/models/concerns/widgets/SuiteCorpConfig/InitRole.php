<?php

namespace common\models\concerns\widgets\SuiteCorpConfig;

use common\helpers\Data;
use common\helpers\Maker;
use common\models\SuiteCorpConfig;
use common\models\SuitePackage;
use common\models\SuiteRole;
use common\models\SuiteRolePermission;
use Illuminate\Support\Arr;

/**
 * @method SuiteCorpConfig|static corp($value = null)
 */
class InitRole
{
    use Maker, Data;

    /**
     * @var SuiteCorpConfig
     */
    public $corp;

    public function execute()
    {
        if (!$this->corp->package_id) {
            return;
        }

        // 检查是否已经有了角色
        $hasRoles = SuiteRole::find()
            ->where([
                'corp_id'  => $this->corp->corp_id,
                'suite_id' => $this->corp->suite_id,
                'type'     => 1
            ])
            ->count();

        if ($hasRoles) {
            return;
        }

        $package = SuitePackage::find()
            ->with('rolePackages.role')
            ->where(['id' => $this->corp->package_id])
            ->one();

        if (!$package) {
            return;
        }


        $packageRoles = Arr::pluck($package->rolePackages, 'role');

        foreach ($packageRoles as $packageRole) {
            $attributes = Arr::except($packageRole->toArray(), 'id');

            $role = new SuiteRole();
            $role->setAttributes($attributes, false);
            $role->suite_id   = $this->corp->suite_id;
            $role->corp_id    = $this->corp->corp_id;
            $role->kind       = 1;
            $role->type       = 1;
            $role->is_default = $packageRole->is_admin == YES ? NO : YES;
            $role->created_at = time();
            $role->updated_at = time();
            $role->save();

            // 整理权限
            $permissionIds = SuiteRolePermission::find()
                ->select('permission_id')
                ->where(['role_id' => $packageRole->id])
                ->column();

            $insert = array_map(function ($permissionId) use ($role) {
                return [
                    'role_id'       => $role->id,
                    'permission_id' => $permissionId,
                ];
            }, $permissionIds);

            if ($insert) {
                SuiteRolePermission::batchInsert($insert);
            }
        }
    }
}
