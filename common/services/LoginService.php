<?php

namespace common\services;

use common\helpers\DataHelper;
use common\models\Account;
use common\models\SuiteCorpAccountsDepartment;
use common\models\SuiteCorpConfig;
use common\models\SuiteCorpDepartment;
use common\models\SuitePackage;
use common\models\SuitePermission;
use common\models\SuiteRole;
use common\models\SuiteRoleAccount;
use common\models\SuiteRolePermission;
use Symfony\Component\Console\Output\NullOutput;
use Yii;
use common\errors\Code;
use common\errors\ErrException;

/**
 * Class LoginService
 * @package common\services
 */
class LoginService extends Service
{

    const ROOT_DEPARTMENT_ID = 1; // 根结点的部门ID

    const PERMISSIONS_ALL_SUITE      = 'suite.*';       // 服务商 全部权限标识
    const PERMISSIONS_ALL_SUITE_CORP = 'suite.corp.*';  // 服务商下级企业管理员全部权限标识

    /**
     * @param string $userid
     * @return string
     * @throws \Random\RandomException
     */
    public static function generateTokenString(string $userid): string
    {
        return hash('sha256', $userid . microtime(true) . random_bytes(32));
    }

    /**
     * 生成登录态数据 通过code码
     * @param $params
     * @return array
     * @throws ErrException
     */
    public static function generateLoginDataByCode($params): array
    {
        if (empty($params['code']) || empty($params['suite_id'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        $userInfo = SuiteService::getUserInfo3rd($params['suite_id'], $params['code']);
        if (empty($userInfo['userid'])) {
            throw new ErrException(Code::BUSINESS_NOT_OPEN);
        }

        $corpConfig = SuiteCorpConfig::find()
                                     ->where(['corp_id' => $userInfo['corpid']])
                                     ->andWhere(['status' => SuiteCorpConfig::STATUS_1])
                                     ->asArray()
                                     ->one();
        if (empty($corpConfig)) {
            throw new ErrException(Code::BUSINESS_APP_NOT_EXIST, '当前企业未授权应用或企业已禁用');
        }

        $user = SuiteService::getDkUser($corpConfig['suite_id'], $corpConfig['corp_id'], $userInfo['userid']);
        if (!empty($userInfo['user_ticket'])) {
            // 获取访问用户敏感信息
            $userDetail       = SuiteService::getUserDetail3rd($params['suite_id'], $userInfo['user_ticket']);
            $user['avatar']   = $userDetail['avatar'];
            $user['nickname'] = $userDetail['name'];
            $user['qr_code']  = $userDetail['qr_code'] ?? '';
        }
        $account = SuiteCorpAccountService::syncAccountInfo($corpConfig['suite_id'], $corpConfig['corp_id'], $user, false);
        if (empty($account)) {
            throw new ErrException(Code::DATA_ERROR, '用户异常');
        }

        // 生成token字符串
        $token = LoginService::generateTokenString($account['userid']);

        // 获取用户拥有的权限
        $roleIds = SuiteRoleAccount::find()->select(['role_id'])->where(['account_id' => $account['id']])->column();

        // 没有角色的话要绑定默认角色, 如果是管理员的话绑定企业管理员角色
        if (!$roleIds) {
            $adminList = SuiteService::getCorpAgentAdminList($corpConfig['suite_id'], $corpConfig['corp_id'], $corpConfig['suite_agent_id']);
            $admins = collect($adminList['admin']);

            // 判断是不是管理员
            if($admins->where('userid', $userInfo['userid'])->where('auth_type', 1)->count()){
                $defaultRole = SuiteRole::find()
                    ->andWhere([
                        'suite_id'   => $corpConfig['suite_id'],
                        'corp_id'    => $corpConfig['corp_id'],
                        'kind'       => 1,
                        'is_admin'   => YES,
                    ])->one();
            }else{
                $defaultRole = SuiteRole::find()
                    ->andWhere([
                        'suite_id'   => $corpConfig['suite_id'],
                        'corp_id'    => $corpConfig['corp_id'],
                        'kind'       => 1,
                        'is_default' => YES,
                    ])->one();
            }

            $roleIds = [$defaultRole->id];

            $roleAccount             = new SuiteRoleAccount();
            $roleAccount->account_id = $account['id'];
            $roleAccount->role_id    = $defaultRole->id;
            $roleAccount->save();
        }

        $permissionIds = SuiteRolePermission::find()->select(['permission_id'])->where(['role_id' => $roleIds,])->column();
        $permissions = SuitePermission::find()
            ->select(['slug'])
            ->where([
                'id'      => array_values(array_unique($permissionIds)),
                'is_hide' => NO,
                'status'  => YES
            ])
            ->column();

        // 需要返回企业对应的套餐信息
        $package = SuitePackage::find()->where(['id' => $corpConfig['package_id']])->asArray()->one();

        return [
            'token'       => $token,
            'config'      => $corpConfig,
            'account'     => $account,
            'permissions' => $permissions,
            'package'     => $package,
        ];
    }

    /**
     * @param $token
     * @return bool
     * @throws ErrException
     */
    public static function verifyAccessToken($token): bool
    {
        if (empty($token)) {
            throw new ErrException(Code::LOGIN_TOKEN_OVERDUE);
        }
        $redis    = Yii::$app->redis;
        $redisKey = Yii::$app->params["redisPrefix"] . 'token.' . $token;
        $exists   = $redis->exists($redisKey); // 查询key是否存在
        if (!$exists) {
            throw new ErrException(Code::LOGIN_TOKEN_OVERDUE);
        }
        $redis->expire($redisKey, 2 * 60 * 60); // 延期 2小时
        return true;
    }

    /**
     * 获取当前登录用户权限
     * @param $token
     * @return false|mixed
     * @throws ErrException
     */
    public static function getPermissions($token = null)
    {
        if ($token === null) {
            $token = DataHelper::getAuthorizationTokenStr();
            if (empty($token)) {
                throw new ErrException(Code::UNAUTHORIZED);
            }
        }
        $redisKey = Yii::$app->params["redisPrefix"] . 'token.' . $token;
        $data     = Yii::$app->redis->get($redisKey);
        if (empty($data)) {
            throw new ErrException(Code::UNAUTHORIZED);
        }
        $data = json_decode($data, true);
        return empty($data['permissions']) ? false : $data['permissions'];
    }

    /**
     * @param $token
     * @return mixed
     * @throws ErrException
     */
    public static function getTokenData($token = null)
    {
        if ($token === null) {
            $token = DataHelper::getAuthorizationTokenStr();
            if (empty($token)) {
                throw new ErrException(Code::UNAUTHORIZED);
            }
        }
        $redisKey = Yii::$app->params["redisPrefix"] . 'token.' . $token;
        $data     = Yii::$app->redis->get($redisKey);
        if (empty($data)) {
            throw new ErrException(Code::UNAUTHORIZED);
        }
        $result = json_decode($data, true);
        if (json_last_error()) {
            throw new ErrException(Code::UNAUTHORIZED);
        }
        return $result;
    }

    /**
     * 判断当前登录用户是否拥有权限
     * @param $slug
     * @param $permissions
     * @return bool
     * @throws ErrException
     */
    public static function permissionExists($slug = null, $permissions = []): bool
    {
        if (empty($permissions)) {
            $permissions = self::getPermissions();
        }
        if (in_array(self::PERMISSIONS_ALL_SUITE, $permissions['slug'])) {
            return true;
        } elseif (in_array(self::PERMISSIONS_ALL_SUITE_CORP, $permissions['slug'])) {
            return true;
        } else {
            return in_array($slug, $permissions['slug']);
        }
    }

    /**
     * 判断用户路由权限
     * @param $token
     * @return bool
     * @throws ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public static function canRoutesPermissions($token): bool
    {
        $route       = Yii::$app->request->getPathInfo();
        $permissions = self::getPermissions($token);
        Yii::warning('Route:' . $route . ' ,Permissions:' . json_encode($permissions, JSON_UNESCAPED_UNICODE));
        $return = false;

        // 判断是否拥有全部权限
        if (!empty($permissions['slug']) && in_array(self::PERMISSIONS_ALL_SUITE, $permissions['slug'])) {
            $return = true;
        } else {
            if (!empty($permissions['data'])) {
                foreach ($permissions['data'] as $element) {
                    if ($route == $element['route'] || $route == trim($element['route'], '/')) {
                        $return = true;
                        break;
                    }
                }
            }

            if (in_array($route, Yii::$app->params['PUBLIC_AUTH'])) {
                $return = true;
            }
        }
        return $return;
    }

}