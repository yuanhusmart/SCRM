<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\Account;
use common\models\SuiteCorpAccountsDepartment;
use common\models\SuiteCorpConfig;
use common\models\SuiteCorpDepartment;
use common\models\SuiteCorpLicenseActiveInfo;

/**
 * Class SuiteCorpLicenseActiveInfoService
 * @package common\services
 */
class SuiteCorpLicenseActiveInfoService extends Service
{

    // 可修改字段
    const CHANGE_FIELDS = ['license_order_id', 'license_order_info_id', 'suite_id', 'corp_id', 'order_id', 'active_code', 'type', 'status', 'userid', 'create_time', 'active_time', 'updated_active_time', 'expire_time', 'merge_info', 'share_info'];

    /**
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     */
    public static function create($params)
    {
        if (!empty($params['merge_info'])) {
            $params['merge_info'] = json_encode($params['merge_info'], JSON_UNESCAPED_UNICODE);
        }
        if (!empty($params['share_info'])) {
            $params['share_info'] = json_encode($params['share_info'], JSON_UNESCAPED_UNICODE);
        }
        $attributes = self::includeKeys($params, self::CHANGE_FIELDS);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $licenseOrder = SuiteCorpLicenseActiveInfo::findOne(['corp_id' => $attributes['corp_id'], 'active_code' => $attributes['active_code']]);
        // 如果数据不存在 写入主表 + 媒体数据
        if (empty($licenseOrder)) {
            $licenseOrder = new SuiteCorpLicenseActiveInfo();
        }
        $licenseOrder->load($attributes, '');
        //校验参数
        if (!$licenseOrder->validate()) {
            throw new ErrException(Code::PARAMS_ERROR, $licenseOrder->getErrors());
        }
        if (!$licenseOrder->save()) {
            throw new ErrException(Code::CREATE_ERROR, $licenseOrder->getErrors());
        }
        return $licenseOrder->getPrimaryKey();
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
        $data = SuiteCorpLicenseActiveInfo::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        $attributes = self::includeKeys($params, self::CHANGE_FIELDS);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data->attributes = $attributes;
        if (!$data->save()) {
            throw new ErrException(Code::UPDATE_ERROR, $data->getErrors());
        }
        return true;
    }

    /**
     * @param $params
     * @return true
     * @throws ErrException
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public static function delete($params)
    {
        $id = self::getId($params);
        if (empty($id)) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data = SuiteCorpLicenseActiveInfo::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        if (!$data->delete()) {
            throw new ErrException(Code::DATA_ERROR);
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
        if (!$suiteId) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $query = SuiteCorpLicenseActiveInfo::find();

        // 主体
        if ($corpId = self::getString($params, 'corp_id')) {
            $query->andWhere(["corp_id" => $corpId]);
        }

        // 订单id
        if ($orderId = self::getString($params, 'order_id')) {
            $query->andWhere(["order_id" => $orderId]);
        }

        // 激活码
        if ($activeCode = self::getString($params, 'active_code')) {
            $query->andWhere(["active_code" => $activeCode]);
        }

        // 自动授权 1.开启 2.关闭
        if ($isAutoAuth = self::getInt($params, 'is_auto_auth')) {
            $query->andWhere(["is_auto_auth" => $isAutoAuth]);
        }

        // 账号状态：1: 未绑定 2: 已绑定且有效 3: 已过期 4: 待转移 5: 已合并 6: 已分配给下游
        if ($status = self::getArray($params, 'status')) {
            $query->andWhere(['in', 'status', $status]);
        }

        // 工号
        if ($jNumber = self::getString($params, 'jnumber')) {
            $query->andWhere(['Exists',
                Account::find()
                       ->select(Account::tableName() . '.id')
                       ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".suite_id=" . Account::tableName() . ".suite_id")
                       ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".corp_id=" . Account::tableName() . ".corp_id")
                       ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".userid=" . Account::tableName() . ".userid")
                       ->andWhere([Account::tableName() . '.jnumber' => $jNumber])
            ]);
        }

        // 微信昵称
        if ($nickname = self::getString($params, 'nickname')) {
            $query->andWhere(['Exists',
                Account::find()
                       ->select(Account::tableName() . '.id')
                       ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".suite_id=" . Account::tableName() . ".suite_id")
                       ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".corp_id=" . Account::tableName() . ".corp_id")
                       ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".userid=" . Account::tableName() . ".userid")
                       ->andWhere([Account::tableName() . '.nickname' => $nickname])
            ]);
        }

        // 账号状态: 1=已激活，2=已禁用，4=未激活，5=退出企业，10=已删除。
        if ($accountStatus = self::getInt($params, 'account_status')) {
            if ($accountStatus == 10) {
                $statusWhere = ['>', Account::tableName() . ".deleted_at", 0];
            } else {
                $statusWhere = ['AND', ['=', Account::tableName() . ".status", $accountStatus], ['=', Account::tableName() . ".deleted_at", 0]];
            }
            $query->andWhere(['Exists',
                Account::find()
                       ->select(Account::tableName() . '.id')
                       ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".suite_id=" . Account::tableName() . ".suite_id")
                       ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".corp_id=" . Account::tableName() . ".corp_id")
                       ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".userid=" . Account::tableName() . ".userid")
                       ->andWhere($statusWhere)
            ]);
        }

        // 部门搜索
        if ($departmentPath = self::getString($params, 'department_path')) {
            $query->andWhere(['Exists',
                Account::find()->alias('acc')
                       ->select('acc.id')
                       ->leftJoin(SuiteCorpAccountsDepartment::tableName() . ' AS accDep', 'acc.suite_id = accDep.suite_id AND acc.corp_id = accDep.corp_id AND acc.userid =accDep.userid')
                       ->leftJoin(SuiteCorpDepartment::tableName() . ' AS dep', 'dep.suite_id = accDep.suite_id AND dep.corp_id = accDep.corp_id AND dep.department_id =accDep.department_id')
                       ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".suite_id = acc.suite_id")
                       ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".corp_id = acc.corp_id")
                       ->andWhere(SuiteCorpLicenseActiveInfo::tableName() . ".userid = acc.userid")
                       ->andWhere(['like', 'dep.path', $departmentPath . '%', false])
            ]);
        }

        $total  = $query->count();
        $offset = ($page - 1) * $per_page;
        $resp   = [];
        if ($total > 0) {
            $resp = $query->with(['accountInfo.accountsDepartmentByAccount.departmentByAccountsDepartment'])
                          ->orderBy(['created_at' => SORT_DESC])
                          ->offset($offset)
                          ->limit($per_page)
                          ->asArray()
                          ->all();
        }
        return [
            'LicenseActiveInfo' => $resp,
            'pagination'        => [
                'page'     => $page,
                'per_page' => $per_page,
                'total'    => intval($total)
            ]
        ];
    }

    /**
     * 获取员工激活码过期时间
     * @param $params
     * @return array|\yii\db\ActiveRecord[]
     * @throws ErrException
     */
    public static function getUseridActiveCodeExpireTime($params)
    {
        $userid  = self::getArray($params, 'userid');
        $suiteId = self::getString($params, 'suite_id');
        $corpId  = self::getString($params, 'corp_id');
        if (!$suiteId || !$corpId || !$userid) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $query = SuiteCorpLicenseActiveInfo::find()
                                           ->select('id,suite_id,corp_id,order_id,active_code,type,userid,expire_time')
                                           ->with(['accountInfo'])
                                           ->andWhere(["suite_id" => $suiteId])
                                           ->andWhere(["corp_id" => $corpId])
                                           ->andWhere(['in', 'userid', $userid])
                                           ->andWhere(["status" => SuiteCorpLicenseActiveInfo::STATUS_2])
                                           ->andWhere(['>', 'expire_time', time()])
                                           ->asArray()
                                           ->all();
        return $query;
    }

    /**
     * @param $params
     * @return true
     * @throws ErrException
     */
    public static function updateActiveTimeByCorpAndUserId($params)
    {
        $data = SuiteCorpLicenseActiveInfo::find()
                                          ->andWhere(['corp_id' => $params['corp_id'], 'userid' => $params['userid']])
                                          ->andWhere(['in', 'status', [SuiteCorpLicenseActiveInfo::STATUS_2, SuiteCorpLicenseActiveInfo::STATUS_4]])
                                          ->one();
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        $data->updated_active_time = time();
        if (!$data->save()) {
            throw new ErrException(Code::UPDATE_ERROR, $data->getErrors());
        }
        return true;
    }

    /**
     * @param $params
     * @return array
     */
    public static function getLicenseActiveGroupInfo($params)
    {
        $query = SuiteCorpLicenseActiveInfo::find()
                                           ->alias('a')
                                           ->leftJoin(SuiteCorpConfig::tableName() . ' AS b', 'b.suite_id = a.suite_id and b.corp_id = a.corp_id')
                                           ->select('a.id,a.suite_id,a.corp_id,a.userid');

        if ($id = self::getInt($params, 'id')) {
            $query->andWhere(['b.id' => $id]);
        }

        // 查询 服务商接口调用许可信息 下的 互通账号 并且 状态为已绑定且有效
        $activeInfo = $query->andWhere(['a.type' => SuiteCorpLicenseActiveInfo::TYPE_2])
                            ->andWhere(['a.status' => SuiteCorpLicenseActiveInfo::STATUS_2])
                            ->asArray()
                            ->all();

        // 根据企业ID进行分组
        $corpExternalBatch = [];
        foreach ($activeInfo as $item) {
            $corpExternalBatch[$item['corp_id']]['userid_list'][] = $item['userid'];
            $corpExternalBatch[$item['corp_id']]['suite_id']      = $item['suite_id'];
            $corpExternalBatch[$item['corp_id']]['corp_id']       = $item['corp_id'];
        }
        return $corpExternalBatch;
    }

    /**
     * @param $params
     * @return true
     * @throws ErrException
     */
    public static function updateIsAutoAuth($params)
    {
        $id         = self::getId($params);
        $isAutoAuth = self::getInt($params, 'is_auto_auth');
        if (!$id || !$isAutoAuth || !in_array($isAutoAuth, array_keys(SuiteCorpLicenseActiveInfo::ACTIVE_IS_AUTO_AUTH_DESC))) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $data = SuiteCorpLicenseActiveInfo::findOne($id);
        if (!$data) {
            throw new ErrException(Code::NOT_EXIST);
        }
        if ($data->is_auto_auth == $isAutoAuth) {
            throw new ErrException(Code::NOT_EXIST, '当前：' . SuiteCorpLicenseActiveInfo::ACTIVE_IS_AUTO_AUTH_DESC[$isAutoAuth] . ',自动授权状态一致无需改变');
        }
        $data->is_auto_auth = $isAutoAuth;
        if (!$data->save()) {
            throw new ErrException(Code::UPDATE_ERROR, $data->getError());
        }
        return true;
    }

}
