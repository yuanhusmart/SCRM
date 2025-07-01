<?php

namespace console\controllers;

use common\models\SuiteCorpConfig;
use common\services\Service;
use common\services\SuiteCorpAccountService;
use common\services\SuiteCorpDepartmentService;
use common\services\SuiteService;

class DepartmentController extends BaseConsoleController
{
    /**
     *  同步主体相关数据
     *  包括部门, 帐号和员工
     *
     *  php ./yii department/sync 4
     *
     * @param $id int 企业内部主键ID
     * @param int $id
     * @return bool
     * @throws \common\errors\ErrException
     */
    public function actionSync(int $id = 0)
    {
        if (!$id) {
            self::consoleLog(">> 请输入企业内部ID}");
            return false;
        }
        $corps = SuiteCorpConfig::find()->where(['id' => $id])->all();
        foreach ($corps as $corpConfig) {
            self::consoleLog(">> 正在处理主体: {$corpConfig->corp_id}");
            $departments = SuiteService::getDepartmentSimpleList($corpConfig->suite_id, $corpConfig->corp_id);
            self::consoleLog($departments);
            $tree = Service::toTreeByList($departments, 0, 'id', 'parentid');
            self::generatePath($tree);
            $list = Service::toListByTree($tree);
            // 入库
            foreach ($list as $item) {
                self::consoleLog(">> 部门: {$item['id']}");
                self::consoleLog($item);
                $item['suite_id']      = $corpConfig->suite_id;
                $item['corp_id']       = $corpConfig->corp_id;
                $item['department_id'] = $item['id'];
                $department            = SuiteCorpDepartmentService::createOrUpdate($item);
                // 同步部门员工数据
                $users = SuiteService::getDepartmentUsers($corpConfig->suite_id, $corpConfig->corp_id, $department->department_id);
                foreach ($users as $user) {
                    self::consoleLog(">> 员工: {$user['name']}");
                    self::consoleLog($user);
                    SuiteCorpAccountService::syncAccountInfo($corpConfig->suite_id, $corpConfig->corp_id, $user);
                }
            }
            self::consoleLog(">> 主体完成: {$corpConfig->corp_id}");
        }
        self::consoleLog(">> 全部完成}");
        return true;
    }

    /**
     * @param $tree
     * @param $path
     * @return void
     */
    private static function generatePath(&$tree, $path = [])
    {
        foreach ($tree as &$item) {
            $arr          = array_merge($path, (array)$item['id']);
            $item['path'] = join('-', $arr);
            if (key_exists('children', $item)) {
                self::generatePath($item['children'], $arr);
            }
        }
    }

    /**
     * 组织架构全量同步（所有企业），每天凌晨4点执行
     * php ./yii department/full-sync
     * @return void
     * @throws \common\errors\ErrException
     */
    public function actionFullSync()
    {
        self::consoleLog(">> 企业通讯录全量同步开始}");
        $corpAll = SuiteCorpConfig::find()->select('id')->all();
        foreach ($corpAll as $item) {
            $this->actionSync($item->id);
        }
        self::consoleLog(">> 企业通讯录全量同步完成}");
    }

}