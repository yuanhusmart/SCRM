<?php

namespace app\controllers;

use common\components\AppController;
use common\components\BaseController;
use common\errors\Code;
use common\models\Account;
use common\models\SuiteRoleAccount;
use common\services\SuiteCorpAccountService;
use common\services\SuiteCorpHistoryAuthUserListService;

class SuiteCorpAccountController extends BaseController
{

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionList()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpAccountService::items($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionAccountsDepartmentList()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpAccountService::accountsDepartmentItems($params);
        return $this->responseSuccess($data);
    }

    /**
     * @return \yii\web\Response
     * @throws \common\errors\ErrException
     */
    public function actionHistoryAuthUserList()
    {
        $params = $this->getBodyParams();
        $data   = SuiteCorpHistoryAuthUserListService::items($params);
        return $this->responseSuccess($data);
    }

    /**
     * 员工设置手机号或系统授权
     * path: /suite-corp-account/set
     */
    public function actionSet()
    {
        $id   = $this->input('id');
        $data = $this->only([
            'mobile',
            'system_auth'
        ]);

        $account = Account::findOne($id);

        if (!$account) {
            return $this->responseError(Code::PARAMS_ERROR, '数据不存在');
        }

        if ($data) {
            $account->setAttributes($data, false);
            $account->save();
        }

        return $this->responseSuccess();
    }

    /**
     * 员工设置角色
     * path: /suite-corp-account/set-role
     */
    public function actionSetRole()
    {
        $id      = $this->input('id');
        $roleIds = $this->input('role_id');

        $account = Account::findOne($id);

        if (!$account) {
            return $this->responseError(Code::PARAMS_ERROR, '数据不存在');
        }

        $insert = array_map(function ($roleId) use ($account) {
            return [
                'account_id' => $account->id,
                'role_id'    => $roleId
            ];
        }, (array)$roleIds);

        SuiteRoleAccount::deleteAll(['account_id' => $account->id]);
        if ($insert) {
            SuiteRoleAccount::batchInsert($insert);
        }

        return $this->responseSuccess();
    }
}