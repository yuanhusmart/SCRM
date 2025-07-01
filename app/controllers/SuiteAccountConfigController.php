<?php

namespace app\controllers;

use common\components\AppController;
use common\models\SuiteAccountConfig;

class SuiteAccountConfigController extends AppController
{

    /**
     * 帐号配置获取
     * path: /suite-account-config/set
     */
    public function actionGet()
    {
        $key       = $this->input('key');
        $accountId = $this->input('account_id', auth()->accountId());

        $paginator = SuiteAccountConfig::find()
            ->when($accountId, function ($query, $accountId) {
                $query->andWhere(['account_id' => $accountId]);
            })
            ->when($key, function ($query, $key) {
                $query->andWhere(['key' => $key]);
            })
            ->paginate($this->input('per_page', 20));

        return $this->responsePaginator($paginator);
    }

    /**
     * 帐号配置设置
     * path: /suite-account-config/set
     */
    public function actionSet()
    {
        $key       = $this->input('key');
        $value     = $this->input('value');
        $accountId = $this->input('account_id', auth()->accountId());

        SuiteAccountConfig::updateOrCreate([
            'key'        => $key,
            'account_id' => $accountId,
        ], [
            'value' => is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value,
        ]);

        return $this->responseSuccess();
    }


}