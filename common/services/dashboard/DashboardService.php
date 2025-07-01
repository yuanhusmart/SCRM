<?php

namespace common\services\dashboard;

use common\helpers\Maker;
use common\models\SuiteAccountConfig;
use common\models\SuiteCorpDepartment;
use Illuminate\Support\Str;

class DashboardService
{
    use Maker;

    /**
     * 获取首页配置的部门数据
     * @return array $departments 对应深度的部门数据
     */
    public function getDepartments()
    {
        $suiteId = auth()->suiteId();
        $corpId  = auth()->corpId();

        $config = SuiteAccountConfig::find()
            ->andWhere(['account_id' => auth()->accountId()])
            ->andWhere(['key' => 'department_rank_config'])
            ->one();

        if (!$config) {
            throw new \Exception('请先进行数据配置');
        }

        $config        = is_array($config->value) ? $config->value : json_decode($config->value, true);
        $departmentIds = $config['department_ids'];
        $depth         = $config['depth'];

        $paths = SuiteCorpDepartment::find()
            ->select('path')
            ->andWhere(['suite_id' => $suiteId])
            ->andWhere(['corp_id' => $corpId])
            ->andWhere(['in', 'department_id', $departmentIds])
            ->column();

        $departments = SuiteCorpDepartment::find()
            ->andWhere(['suite_id' => $suiteId])
            ->andWhere(['corp_id' => $corpId])
            ->andWhere(value(function () use ($paths) {
                $data   = [];
                $data[] = 'OR';

                foreach ($paths as $path) {
                    $data[] = ['like', 'path', $path . '-%', false];
                }

                return $data;
            }))
            ->asArray()
            ->all();

        return array_filter($departments, function ($item) use ($depth) {
            return count(explode('-', $item['path'])) == $depth;
        });
    }

    /**
     * 获取首页配置的部门数据, 并附带所有子孙数据
     * @return array
     */
    public function getDepartmentsWithChildren()
    {
        $departments = $this->getDepartments();

        $children = SuiteCorpDepartment::find()
            ->andWhere(value(function () use ($departments) {
                $data   = [];
                $data[] = 'OR';
                foreach ($departments as $department) {
                    $data[] = ['like', 'path', $department['path'] . '-%', false];
                }
                return $data;
            }))
            ->asArray()
            ->all();

        $collection = collect($children);

        foreach ($departments as &$department) {
            $items = $collection->filter(function ($item) use ($department) {
                return Str::startsWith($item['path'], $department['path'] . '-');
            })->all();

            $department['children'] = $items;
        }

        return $departments;
    }

    /**
     * 获取通过员工质检设置的部门信息
     */
    public function getDepartmentsOfStaffQuality()
    {
        $suiteId = auth()->suiteId();
        $corpId  = auth()->corpId();

        $config = SuiteAccountConfig::find()
            ->andWhere(['account_id' => auth()->accountId()])
            ->andWhere(['key' => 'employee_quality_check'])
            ->one();

        if (!$config) {
            throw new \Exception('请先进行数据配置');
        }

        $config        = is_array($config->value) ? $config->value : json_decode($config->value, true);
        $departmentIds = $config['department_ids'];

        return SuiteCorpDepartment::find()
            ->andWhere(['suite_id' => $suiteId])
            ->andWhere(['corp_id' => $corpId])
            ->andWhere(['in', 'department_id', $departmentIds])
            ->asArray()
            ->all();
    }
}