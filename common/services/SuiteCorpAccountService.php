<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\Account;
use common\models\SuiteCorpAccountsDepartment;
use common\models\SuiteCorpConfigChatAuth;
use common\models\SuiteCorpDepartment;
use common\models\SuiteCorpExternalContactFollowUser;
use common\models\SuiteCorpGroupChatMember;
use common\models\SuiteCorpLicenseActiveInfo;

/**
 * Class SuiteCorpAccountService
 * @package common\services
 */
class SuiteCorpAccountService extends Service
{

    const CHANGE_FIELDS = ['suite_id', 'corp_id', 'userid', 'nickname', 'avatar', 'status', 'friends_number', 'groups_number', 'login_times', 'logout_times'];

    /**
     * @param $suiteId
     * @param $corpId
     * @param $user
     * @return mixed
     * @throws ErrException
     * @throws \yii\db\Exception
     */
    public static function syncAccountInfo($suiteId, $corpId, $user, $syncDep = true)
    {
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $user['suite_id']          = $suiteId;
            $user['corp_id']           = $corpId;
            $user['nickname']          = $user['name'] ?? '';
            $user['avatar']            = $user['avatar'] ?? '';
            $user['is_leader_in_dept'] = $user['is_leader_in_dept'] ?? [];
            $user['id']                = self::createOrUpdate($user);
            if ($syncDep) {
                // 清除 企业员工帐号所在部门表
                SuiteCorpAccountsDepartmentService::deleteAllByUserid($suiteId, $corpId, $user['userid']);
                // 添加 企业员工帐号所在部门表
                SuiteCorpAccountsDepartmentService:: batchInsertAccountsDepartment($suiteId, $corpId, $user['userid'], $user['department'], $user['is_leader_in_dept']);
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
        return $user;
    }

    /**
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     */
    public static function createOrUpdate($params)
    {
        $attributes = self::includeKeys($params, self::CHANGE_FIELDS);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        if (empty($attributes['suite_id']) || empty($attributes['corp_id']) || empty($attributes['userid'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $time    = time();
        $account = Account::findOne(['suite_id' => $attributes['suite_id'], 'corp_id' => $attributes['corp_id'], 'userid' => $attributes['userid']]);
        if (empty($account)) {
            $account                  = new Account();
            $attributes['created_at'] = $time;
        }
        $attributes['deleted_at'] = 0;
        $attributes['updated_at'] = $time;
        $account->load($attributes, '');
        //校验参数
        if (!$account->validate()) {
            throw new ErrException(Code::PARAMS_ERROR, $account->getError());
        }
        if (!$account->save()) {
            throw new ErrException(Code::CREATE_ERROR, $account->getError());
        }
        return $account->getPrimaryKey();
    }

    /**
     * @param $params
     * @return true
     * @throws ErrException
     */
    public static function update($params)
    {
        $id = self::getId($params);
        if (!$id) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data = Account::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        $attributes = self::includeKeys($params, self::CHANGE_FIELDS);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data->attributes = $attributes;
        if (!$data->save()) {
            throw new ErrException(Code::UPDATE_ERROR, $data->getError());
        }
        return true;
    }

    /**
     * @param $params
     * @return array
     * @throws ErrException
     */
    public static function items($params)
    {
        list($page, $per_page) = self::getPageInfo($params);
        $suiteId = self::getString($params, 'suite_id');
        $corpId  = self::getString($params, 'corp_id');
        if (!$suiteId || !$corpId) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $query = Account::find()
                        ->alias('account')
                        ->leftJoin(SuiteCorpAccountsDepartment::tableName() . ' AS ad', 'account.suite_id = ad.suite_id AND account.corp_id = ad.corp_id AND account.userid = ad.userid')
                        ->accessControl('account.id')
                        ->andWhere(["account.suite_id" => $suiteId])
                        ->andWhere(["account.corp_id" => $corpId]);

        if ($nickname = self::getString($params, 'nickname')) {
            $query->andWhere(["account.nickname" => $nickname]);
        }

        // 部门ID
        if ($departmentId = self::getArray($params, 'department_id')) {
            $query->andWhere(['Exists',
                SuiteCorpDepartment::find()
                                   ->alias('a')
                                   ->select('a.department_id')
                                   ->leftJoin(SuiteCorpDepartment::tableName() . ' AS b', 'a.suite_id = b.suite_id AND a.corp_id = b.corp_id AND a.path LIKE CONCAT(b.path, "%")')
                                   ->andWhere("b.suite_id=account.suite_id")
                                   ->andWhere("b.corp_id=account.corp_id")
                                   ->andWhere(['in', 'b.department_id', $departmentId])
                                   ->andWhere("a.suite_id=ad.suite_id")
                                   ->andWhere("a.corp_id=ad.corp_id")
                                   ->andWhere("a.department_id=ad.department_id")
            ]);
        }

        // 微信userid
        if ($userid = self::getArray($params, 'userid')) {
            $query->andWhere(['in', 'account.userid', $userid]);
        }

        // 状态: 1=已激活，2=已禁用，4=未激活，5=退出企业
        if ($status = self::getArray($params, 'status')) {
            $query->andWhere(['IN', "account.status", $status]);
        }

        if ($mobile = self::getString($params, 'mobile')) {
            $query->andWhere(["account.mobile" => $mobile]);
        }

        // 服务商接口调用许可订单详情表 账号类型：1:基础账号，2:互通账号，3:未使用
        if ($licenseActiveType = self::getInt($params, 'license_active_type')) {
            if ($licenseActiveType == 3) {
                $firstWhere        = 'NOT Exists';
                $licenseActiveType = [SuiteCorpLicenseActiveInfo::TYPE_1, SuiteCorpLicenseActiveInfo::TYPE_2];

            } else {
                $firstWhere        = 'Exists';
                $licenseActiveType = [$licenseActiveType];
            }
            $query->andWhere([$firstWhere,
                SuiteCorpLicenseActiveInfo::find()
                                          ->select(SuiteCorpLicenseActiveInfo::tableName() . '.id')
                                          ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".suite_id=account.suite_id")
                                          ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".corp_id=account.corp_id")
                                          ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".userid=account.userid")
                                          ->andWhere(['in', SuiteCorpLicenseActiveInfo::tableName() . ".type", $licenseActiveType])
                                          ->andWhere([SuiteCorpLicenseActiveInfo::tableName() . '.status' => SuiteCorpLicenseActiveInfo::STATUS_2])
            ]);
        }

        // 服务商接口调用许可订单详情表 账号状态：1: 未绑定 2: 已绑定且有效 3: 已过期 4: 待转移 5: 已合并 6: 已分配给下游
        if ($licenseActiveStatus = self::getInt($params, 'license_active_status')) {
            $query->andWhere(['Exists',
                SuiteCorpLicenseActiveInfo::find()
                                          ->select(SuiteCorpLicenseActiveInfo::tableName() . '.id')
                                          ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".suite_id=account.suite_id")
                                          ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".corp_id=account.corp_id")
                                          ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".userid=account.userid")
                                          ->andWhere([SuiteCorpLicenseActiveInfo::tableName() . '.status' => $licenseActiveStatus])
            ]);
        }

        $total  = $query->count("DISTINCT account.id");
        $offset = ($page - 1) * $per_page;
        $resp   = [];
        if ($total > 0) {
            $query
                ->with([
                    'accountsDepartmentByAccount.departmentByAccountsDepartment',
                    'licenseActiveInfoByAccount',
                    'roles'
                ])
                ->select([
                    "account.id",
                    "account.suite_id",
                    "account.corp_id",
                    "account.userid",
                    "account.nickname",
                    "account.avatar",
                    "account.status",
                    "account.mobile",
                    "account.system_auth"
                ]);
            $resp = $query->orderBy(['account.id' => SORT_DESC])->groupBy('account.id')->offset($offset)->limit($per_page)->asArray()->all();

            $userIds   = array_column($resp, 'userid');
            $userCount = SuiteCorpExternalContactFollowUser::find()
                                                           ->where(['in', 'userid', $userIds])
                                                           ->andWhere(['deleted_at' => 0])
                                                           ->select('count(DISTINCT external_contact_id) as external_contact_counts,userid')
                                                           ->groupBy('userid')
                                                           ->asArray()
                                                           ->indexBy('userid')
                                                           ->all();

            $groupCount = SuiteCorpGroupChatMember::find()
                                                  ->where(['in', 'userid', $userIds])
                                                  ->andWhere(['type' => SuiteCorpGroupChatMember::GROUP_CHAT_TYPE_1])
                                                  ->select('count(DISTINCT group_chat_id) as group_chat_counts,userid')
                                                  ->groupBy('userid')
                                                  ->asArray()
                                                  ->indexBy('userid')
                                                  ->all();

            foreach ($resp as &$value) {
                $value['external_contact_counts'] = empty($userCount[$value['userid']]) ? 0 : $userCount[$value['userid']]['external_contact_counts'];
                $value['group_chat_counts']       = empty($groupCount[$value['userid']]) ? 0 : $groupCount[$value['userid']]['group_chat_counts'];
            }
        }
        return [
            'Account'    => $resp,
            'pagination' => [
                'page'     => $page,
                'per_page' => $per_page,
                'total'    => intval($total)
            ]
        ];
    }

    /**
     * @param $params
     * @return true
     * @throws ErrException
     */
    public static function delete($params)
    {
        if (empty($params['suite_id']) || empty($params['corp_id']) || empty($params['userid'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $account = Account::findOne(['suite_id' => $params['suite_id'], 'corp_id' => $params['corp_id'], 'userid' => $params['userid']]);
        if (!$account) {
            throw new ErrException(Code::NOT_EXIST);
        }
        $account->deleted_at = $params['deleted_at'];
        $account->status     = Account::ACCOUNT_STATUS_5;
        if (!$account->save()) {
            throw new ErrException(Code::DATA_ERROR);
        }
        return true;
    }

    /**
     * 员工列表（通讯录场景）
     * @param $params
     * @return array
     * @throws ErrException
     */
    public static function accountsDepartmentItems($params)
    {
        list($page, $per_page) = self::getPageInfo($params);
        $suiteId = self::getString($params, 'suite_id');
        $corpId  = self::getString($params, 'corp_id');
        if (!$suiteId || !$corpId) {
            throw new ErrException(Code::PARAMS_ERROR);
        }

        $query = Account::find()
                        ->accessControl('id')
                        ->select(["id", "suite_id", "corp_id", "userid", "avatar", 'status', 'deleted_at'])
                        ->andWhere(["suite_id" => $suiteId])
                        ->andWhere(["corp_id" => $corpId]);

        // 删除时间 - 开始 - 截止
        if ($deletedAt = self::getArray($params, 'deleted_at')) {
            $query->andWhere(['BETWEEN', 'deleted_at', $deletedAt[0] ?? 1, $deletedAt[1] ?? time()]);
        } else {
            $query->andWhere(["deleted_at" => 0]);
        }

        // 微信userid
        if ($userid = self::getArray($params, 'userid')) {
            $query->andWhere(['in', 'userid', $userid]);
        }

        // 部门ID  (不包含子集)
        if ($departmentId = self::getArray($params, 'department_id')) {
            $query->andWhere(['Exists',
                SuiteCorpAccountsDepartment::find()
                                           ->andWhere(Account::tableName() . ".suite_id=" . SuiteCorpAccountsDepartment::tableName() . ".suite_id")
                                           ->andWhere(Account::tableName() . ".corp_id=" . SuiteCorpAccountsDepartment::tableName() . ".corp_id")
                                           ->andWhere(Account::tableName() . ".userid=" . SuiteCorpAccountsDepartment::tableName() . ".userid")
                                           ->andWhere(['in', 'department_id', $departmentId])
            ]);
        }

        // 部门路径 (包含子集)
        if ($departmentPath = self::getArray($params, 'department_path')) {
            $query->andWhere(['Exists',
                SuiteCorpAccountsDepartment::find()
                                           ->alias('ad')
                                           ->leftJoin(SuiteCorpDepartment::tableName() . ' AS department', 'ad.suite_id = department.suite_id AND ad.corp_id = department.corp_id AND ad.department_id = department.department_id')
                                           ->leftJoin(SuiteCorpDepartment::tableName() . ' AS departmentSub', 'departmentSub.suite_id = department.suite_id AND departmentSub.corp_id = department.corp_id AND department.path LIKE CONCAT(departmentSub.path, "%")')
                                           ->andWhere("ad.suite_id=" . Account::tableName() . ".suite_id")
                                           ->andWhere("ad.corp_id=" . Account::tableName() . ".corp_id")
                                           ->andWhere("ad.userid=" . Account::tableName() . ".userid")
                                           ->andWhere(['in', 'departmentSub.path', $departmentPath])
            ]);
        }


        // 状态: 1=已激活，2=已禁用，4=未激活，5=退出企业
        if ($status = self::getArray($params, 'status')) {
            $query->andWhere(['IN', "status", $status]);
        }

        // 是否存档: 1是, 2否
        if ($isArchived = self::getInt($params, 'is_archived')) {
            $queryExists = 'NOT Exists';
            if ($isArchived == 1) {
                $queryExists = 'Exists';
            }
            $query->andWhere([$queryExists,
                SuiteCorpConfigChatAuth::find()
                                       ->andWhere(Account::tableName() . ".suite_id=" . SuiteCorpConfigChatAuth::tableName() . ".suite_id")
                                       ->andWhere(Account::tableName() . ".corp_id=" . SuiteCorpConfigChatAuth::tableName() . ".corp_id")
                                       ->andWhere(Account::tableName() . ".userid=" . SuiteCorpConfigChatAuth::tableName() . ".userid")
            ]);
        }

        $total  = $query->count("DISTINCT id");
        $offset = ($page - 1) * $per_page;
        $resp   = [];
        if ($total > 0) {
            $resp = $query->with(['chatAuthByAccount'])->orderBy(['id' => SORT_DESC])->offset($offset)->limit($per_page)->asArray()->all();
        }
        return [
            'Account'    => $resp,
            'pagination' => [
                'page'     => $page,
                'per_page' => $per_page,
                'total'    => intval($total)
            ]
        ];
    }

}