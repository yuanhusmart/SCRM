<?php

namespace common\models\concerns\widgets\SuiteCorpConfig;

use common\helpers\Data;
use common\helpers\Maker;
use common\models\SuiteCorpConfig;
use common\models\SuiteRole;
use common\models\SuiteRolePackage;
use common\models\SuiteRolePermission;

/**
 * @method SuiteCorpConfig|static corp($value = null)
 */
class RevisePackageRole
{
    use Maker, Data;

    /**
     * @var SuiteCorpConfig
     */
    public $corp;

    /**
     * 修正管理员权限
     * 修正其他人员的权限
     */
    public function execute()
    {
        if (!$this->corp->package_id) {
            return;
        }

        // 需要先初始化一下角色
        InitRole::make()->corp($this->corp)->execute();

        $packageAdminId = SuiteRolePackage::find()
            ->select('role_id')
            ->andWhere(['package_id' => $this->corp->package_id, 'kind' => 1])
            ->column();

        $permissionIds = SuiteRolePermission::find()
            ->select('permission_id')
            ->andWhere(['role_id' => $packageAdminId])
            ->column();

        $admin = SuiteRole::find()
            ->andWhere(['suite_id' => $this->corp->suite_id])
            ->andWhere(['corp_id' => $this->corp->corp_id])
            ->andWhere(['is_admin' => YES])
            ->one();

        if (!$admin) {
            return;
        }

        // 管理员的权限需要全部替换
        SuiteRolePermission::deleteAll([
            'role_id' => $admin->id,
        ]);

        $insert = array_map(function ($permissionId) use ($admin) {
            return [
                'role_id'       => $admin->id,
                'permission_id' => $permissionId,
            ];
        }, $permissionIds);

        if ($insert) {
            SuiteRolePermission::batchInsert($insert);
        }

        $roleIds = SuiteRole::find()
            ->select('id')
            ->andWhere(['suite_id' => $this->corp->suite_id])
            ->andWhere(['corp_id' => $this->corp->corp_id])
            ->andWhere(['!=', 'is_admin', YES])
            ->column();

        // 其他成员的权限需要剔除
        SuiteRolePermission::deleteAll([
            'AND',
            ['role_id' => $roleIds],
            ['NOT IN', 'permission_id', $permissionIds]
        ]);
    }
}

