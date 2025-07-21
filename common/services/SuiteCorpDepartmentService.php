<?php

namespace common\services;

use common\errors\Code;
use common\errors\ErrException;
use common\models\SuiteCorpAccountsDepartment;
use common\models\SuiteCorpDepartment;

/**
 * Class SuiteCorpDepartmentService
 * @package common\services
 */
class SuiteCorpDepartmentService extends Service
{

    /**
     * @param $params
     * @return array|mixed|null
     * @throws ErrException
     */
    public static function createOrUpdate($params)
    {
        $attributes = self::includeKeys($params, SuiteCorpDepartment::CHANGE_FIELDS);
        if (!$attributes) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        if (empty($attributes['suite_id']) || empty($attributes['corp_id']) || empty($attributes['department_id'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $attributes['updated_at'] = empty($attributes['updated_at']) ? time() : $attributes['updated_at'];

        $department = SuiteCorpDepartment::findOne(['suite_id' => $attributes['suite_id'], 'corp_id' => $attributes['corp_id'], 'department_id' => $attributes['department_id']]);
        if (empty($department)) {
            $department = new SuiteCorpDepartment();
        }
        // 如果缺少 部门路径 需要根据 父部门id进行补充
        if (empty($attributes['path'])) {
            $parentDepartment = SuiteCorpDepartment::find()
                                                   ->where(['suite_id' => $attributes['suite_id'], 'corp_id' => $attributes['corp_id'], 'department_id' => $attributes['parentid']])
                                                   ->asArray()
                                                   ->one();
            if ($parentDepartment) {
                $attributes['path'] = $parentDepartment['path'] . '-' . $attributes['department_id'];
            }
        }
        $department->load($attributes, '');
        //校验参数
        if (!$department->validate()) {
            throw new ErrException(Code::PARAMS_ERROR, $department->getError());
        }
        if (!$department->save()) {
            throw new ErrException(Code::CREATE_ERROR, $department->getError());
        }
        return $department;
    }

    /**
     * @param $params
     * @return true
     * @throws ErrException
     */
    public static function delete($params)
    {
        if (empty($params['suite_id']) || empty($params['corp_id']) || empty($params['department_id'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $department = SuiteCorpDepartment::findOne(['suite_id' => $params['suite_id'], 'corp_id' => $params['corp_id'], 'department_id' => $params['department_id']]);
        if (!$department) {
            throw new ErrException(Code::NOT_EXIST);
        }
        $department->deleted_at = $params['deleted_at'];
        if (!$department->save()) {
            throw new ErrException(Code::DATA_ERROR);
        }
        return true;
    }

    /**
     * @param $params
     * @return array
     * @throws ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public static function items($params)
    {
        list($page, $per_page) = self::getPageInfo($params);
        // 服务商ID
        $suiteId = self::getString($params, 'suite_id');
        // 企业ID
        $corpId = self::getString($params, 'corp_id');

        if (!$suiteId || !$corpId) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $query  = SuiteCorpDepartment::find()
                                     ->andWhere(['suite_id' => $suiteId])
                                     ->andWhere(['corp_id' => $corpId])
                                     ->andWhere(['deleted_at' => 0]);
        $total  = $query->count();
        $order  = ['department_id' => SORT_ASC];
        $offset = ($page - 1) * $per_page;
        $resp   = [];
        if ($total > 0) {
            $resp = $query->orderBy($order)->offset($offset)->limit($per_page)->asArray()->all();
        }
        return [
            'Department' => $resp,
            'pagination' => [
                'page'     => $page,
                'per_page' => $per_page,
                'total'    => intval($total)
            ]
        ];
    }

    /**
     * @param $params
     * @return array
     * @throws ErrException
     * @throws \yii\base\InvalidConfigException
     */
    public static function tree($params)
    {
        if ((!$suiteId = self::getString($params, 'suite_id')) || (!$corpId = self::getString($params, 'corp_id'))) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        // 获取企微负责的部门数据
        $getDepartmentLeaderByLoginUser = self::getDepartmentLeaderByLoginUser($params);

        $query = SuiteCorpDepartment::find()->alias('a')
                                    ->leftJoin(SuiteCorpDepartment::tableName() . ' AS b', 'a.suite_id = b.suite_id AND a.corp_id = b.corp_id AND a.path LIKE CONCAT(b.path, "%")')
                                    ->andWhere(['a.suite_id' => $suiteId])
                                    ->andWhere(['a.corp_id' => $corpId])
                                    ->andWhere(['a.deleted_at' => 0]);
        if ($getDepartmentLeaderByLoginUser['leaderDepartmentId']) {
            $query->andWhere(['in', 'b.department_id', $getDepartmentLeaderByLoginUser['leaderDepartmentId']]);
        } else {
            return [];
        }
        $list = $query->select(['a.*'])->orderBy(['a.order' => SORT_DESC])->asArray()->all();

        if ($getDepartmentLeaderByLoginUser['leaderPath'] && !in_array(SuiteCorpDepartment::ROOT_DEPARTMENT_ID, $getDepartmentLeaderByLoginUser['leaderDepartmentId'])) {
            $leaderDepartment = SuiteCorpDepartment::find()
                                                   ->select(['*', 'IF(id IS NULL,0,1) as not_auth'])
                                                   ->andWhere(['suite_id' => $suiteId])
                                                   ->andWhere(['corp_id' => $corpId])
                                                   ->andWhere(['in', 'department_id', $getDepartmentLeaderByLoginUser['leaderPath']])
                                                   ->orderBy(['order' => SORT_DESC])
                                                   ->asArray()
                                                   ->all();
            $list             = array_merge($list, $leaderDepartment);
        }
        return Service::toTreeByList($list, 0, 'department_id', 'parentid');
    }

    /**
     * @param $params
     * @return array
     * @throws ErrException
     */
    public static function treeAll($params)
    {
        if ((!$suiteId = self::getString($params, 'suite_id')) || (!$corpId = self::getString($params, 'corp_id'))) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $list = SuiteCorpDepartment::find()
                                   ->andWhere(['suite_id' => $suiteId])
                                   ->andWhere(['corp_id' => $corpId])
                                   ->andWhere(['deleted_at' => 0])
                                   ->orderBy(['order' => SORT_DESC])
                                   ->asArray()
                                   ->all();
        return Service::toTreeByList($list, 0, 'department_id', 'parentid');
    }

    /**
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function details($params)
    {
        // 服务商ID
        $suiteId = self::getString($params, 'suite_id');
        // 企业ID
        $corpId = self::getString($params, 'corp_id');
        // 部门ID
        $departmentId = self::getInt($params, 'department_id');
        if (!$suiteId || !$corpId || !$departmentId) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $department = SuiteCorpDepartment::find()
                                         ->with('suiteCorpAccountsDepartmentLeaders.accountByDepartmentLeader')
                                         ->andWhere(['suite_id' => $suiteId, 'corp_id' => $corpId, 'department_id' => $departmentId])
                                         ->asArray()
                                         ->one();
        if (!$department) {
            throw new ErrException(Code::DATA_ERROR);
        }
        return $department;
    }

    /**
     * 获取企微负责的部门数据 TODO
     * @param $params
     * @return array|void
     * @throws ErrException
     */
    public static function getDepartmentLeaderByLoginUser($params)
    {
        $tokenData = LoginService::getTokenData();
        $userid    = $tokenData['account']['userid'];

        $suiteId = empty($params['suite_id']) ? $tokenData['config']['suite_id'] : $params['suite_id'];
        $corpId  = empty($params['corp_id']) ? $tokenData['config']['corp_id'] : $params['corp_id'];

        // 获取企微负责的部门数据
        $departmentIdLeader = SuiteCorpAccountsDepartment::find()->alias('a')
                                                         ->leftJoin(SuiteCorpDepartment::tableName() . ' AS b', 'a.suite_id = b.suite_id AND a.corp_id = b.corp_id AND a.department_id=b.department_id')
                                                         ->select(['a.department_id', 'b.path'])
                                                         ->where(['a.suite_id' => $suiteId, 'a.corp_id' => $corpId, 'a.userid' => $userid])
                                                         ->andWhere(['a.is_leader_in_dept' => SuiteCorpAccountsDepartment::IS_LEADER_IN_DEPT_1])
                                                         ->asArray()
                                                         ->all();

        // 负责的部门ID集合
        $leaderDepartmentId = $leaderPath = [];
        // 如果是部门负责人返回数据 leaderDepartmentId 负责的部门ID leaderPath 负责的部门路径
        if (!empty($departmentIdLeader)) {
            foreach ($departmentIdLeader as $leader) {
                // 如果是根节点部门跳出循环
                if ($leader['department_id'] == SuiteCorpDepartment::ROOT_DEPARTMENT_ID) {
                    $leaderDepartmentId[] = SuiteCorpDepartment::ROOT_DEPARTMENT_ID;
                    $leaderPath[]         = SuiteCorpDepartment::ROOT_DEPARTMENT_ID;
                    break;
                }
                $leaderDepartmentId[] = $leader['department_id'];
                // 父级部门链路转为数组
                $leader['path'] = explode('-', $leader['path']);
                // 删除当前部门ID
                unset($leader['path'][array_search($leader['department_id'], $leader['path'])]);
                // 赋值 父级部门ID集合
                $leaderPath = array_merge($leaderPath, $leader['path']);
            }
        }
        return ['leaderDepartmentId' => $leaderDepartmentId, 'leaderPath' => $leaderPath, 'suite_id' => $suiteId, 'corp_id' => $corpId];
    }

}