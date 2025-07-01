<?php

namespace common\components;

use common\models\Account;
use common\models\SuiteCorpAccountsDepartment;
use common\models\SuiteCorpDepartment;
use common\models\SuiteRole;
use common\models\SuiteRoleAccount;
use common\services\LoginService;
use yii\base\Component;
use common\errors\ErrException;
use common\helpers\Maker;
use yii\db\Expression;
use yii\db\Query;

class Auth extends Component
{
    use Maker;

    /**
     * @var array 
     */
    protected $user;

    /**
     * @param array $config
     * @throws ErrException
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->user = LoginService::getTokenData();
    }

    /**
     * 获取当前登录信息
     * @return array
     */
    final function user(): array
    {
        return $this->user;
    }

    /**
     * TK
     * @return string
     */
    final function token(): string
    {
        return $this->user['token'] ?? '';
    }

    /**
     * 企业配置
     * @return array
     */
    final function config(): array
    {
        return $this->user['config'] ?? [];
    }

    /**
     * 服务商ID
     * @return mixed|string
     */
    final function suiteId()
    {
        return $this->config()['suite_id'] ?? '';
    }

    /**
     * 企业ID
     * @return mixed|string
     */
    final function corpId()
    {
        return $this->config()['corp_id'] ?? '';
    }

    /**
     * 员工信息
     * @return array
     */
    final function account(): array
    {
        return $this->user['account'] ?? [];
    }

    /**
     * 员工ID
     * @return mixed|null
     */
    final function accountId()
    {
        return $this->account()['id'] ?? null;
    }

    /**
     * 企微userid
     * @return mixed|string
     */
    final function qwId()
    {
        return $this->account()['userid'] ?? '';
    }

    /**
     * 权限
     * @return array
     */
    final function permissions(): array
    {
        return $this->user['permissions'] ?? [];
    }

    /**
     * 套餐
     * @return array
     */
    final function package(): array
    {
        return $this->user['package'] ?? [];
    }

    /**
     * 是否一个指定企业的管理员角色
     * @return bool
     */
    public function isCorpAdmin(): bool
    {
        $package = $this->package();
        $packageType = $package['type'] ?? -1; //套餐类型: 1企业 2服务商 3试用版
        if ($packageType == 1) {
            return true;
        }

        $accountId = $this->accountId();
        if (!$accountId) {
            return false;
        }
        $roleIds = SuiteRoleAccount::find()->select(['role_id'])->where(['account_id' => $accountId])->column();
        if (!$roleIds) {
            return false;
        }
        return SuiteRole::corp()->andWhere(['id' => $roleIds, 'is_admin' => 1])->exists();
    }

    /**
     * 获取当前登陆人的数据权限范围
     * @param bool $isQuery 是否返回查询对象
     * @return Query|array
     */
    public function getDataPermissionAccountIdBySystem(bool $isQuery = true)
    {
        $unionQuery = (new Query())->select([new Expression('NULL AS id')])->where('1=0');
        $suiteId = $this->suiteId();
        $corpId = $this->corpId();
        $qwId = $this->qwId();
        if (!$suiteId || !$corpId || !$qwId) {
            return $isQuery ? $unionQuery : [];
        }

        if ($this->isCorpAdmin()) {
            $unionQuery = (new Query())
                ->select(['id' => 'id'])
                ->from(Account::tableName())
                ->where(['suite_id' => $suiteId, 'corp_id' => $corpId]);
            return $isQuery ? $unionQuery : array_map('intval', $unionQuery->column());
        }

        //查询部门下所有员工
        $child_department_ids = SuiteCorpAccountsDepartment::corp()
            ->select(['department_id'])
            ->andWhere(['userid' => $qwId, 'is_leader_in_dept' => SuiteCorpAccountsDepartment::IS_LEADER_IN_DEPT_1,])
            ->column();
        $userids = [];
        if ($child_department_ids) {
            $child_department_ids = array_values(array_unique($child_department_ids));
            foreach ($child_department_ids as $k => $v) {
                $child_department_ids[$k] = intval($v);
            }
            $child_department_path = SuiteCorpDepartment::corp()
                ->select(['path'])
                ->andWhere(['department_id' => $child_department_ids,])
                ->asArray()
                ->column();
            if (!empty($child_department_path)) {
                $child_department_where = ['OR'];
                foreach ($child_department_path as $path) {
                    $child_department_where[] = ['like', 'path', $path . '%', false];
                }
                $child_department_ids = SuiteCorpDepartment::corp()->select('department_id')->andWhere($child_department_where)->column();
                if ($child_department_ids) {
                    $userids = SuiteCorpAccountsDepartment::corp()->select(['userid'])->where(['department_id' => $child_department_ids,])->column();
                }
            }
        }

        $userids[] = $qwId;
        $ids = Account::corp()->select(['id'])->andWhere(['userid' => $userids,])->column();
        foreach ($ids as $id) {
            $unionQuery->union((new Query())->select([new Expression("$id AS id")]), true);
        }
        return $isQuery ? $unionQuery : $ids;
    }

    /**
     * 获取下属员工ID
     * @param string $type 返回类型: userid, account_id
     * @param bool $includeSelf 是否包含自己
     */
    public function getStaffUnderling($type = 'account_id', $includeSelf = true)
    {
        $userId = $this->qwId();

        $ids = SuiteCorpAccountsDepartment::corp()
            ->select(['department_id'])
            ->andWhere(['is_leader_in_dept' => SuiteCorpAccountsDepartment::IS_LEADER_IN_DEPT_1])
            ->andWhere(['userid' => $userId])
            ->column();

        $departments = SuiteCorpDepartment::corp()
            ->andWhere(['department_id' => $ids])
            ->asArray()
            ->all();

        $userIds = SuiteCorpAccountsDepartment::find()
            ->alias('ad')
            ->leftJoin('suite_corp_department as d', 'ad.department_id = d.department_id and ad.suite_id=d.suite_id and ad.corp_id=d.corp_id')
            ->select(['ad.userid'])
            ->andWhere([
                'ad.suite_id' => $this->suiteId(),
                'ad.corp_id' => $this->corpId(),
            ])
            ->andWhere(value(function () use ($departments) {
                $data   = [];
                $data[] = 'OR';

                $data[] = ['in', 'd.path', array_column($departments, 'path')];

                foreach ($departments as $department) {
                    $data[] = ['like', 'd.path', $department['path'] . '-%', false];
                }

                return $data;
            }))
            ->column();

        if ($includeSelf) {
            $userIds[] = $userId;
            $userIds = array_values(array_unique($userIds));
        } else {
            $userIds = array_diff($userIds, [$userId]);
            $userIds = array_values(array_unique($userIds));
        }

        if ($type == 'userid') {
            return $userIds;
        }

        return Account::corp()->select(['id'])->andWhere(['userid' => $userIds])->column();
    }

    /**
     * 当前登陆人是否一个指定员工的上级
     * @param $accountId
     * @return bool
     */
    public function isStaffSuperior($accountId)
    {
        if ($this->isCorpAdmin()) {
            return true;
        }
        !is_array($accountId) && $accountId = [$accountId];
        if (array_intersect($accountId, $this->getDataPermissionAccountIdBySystem(false))) {
            return true;
        }
        return false;
    }
}
