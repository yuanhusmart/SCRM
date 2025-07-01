<?php

use common\models\Account;

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php'
);
return [
    'id'                  => 'app',
    'basePath'            => dirname(__DIR__),
    'controllerNamespace' => 'app\controllers',
    'timeZone' => 'Asia/Shanghai',
    'bootstrap'           => ['log'],
    'components'          => [
        'request'      => [
            'cookieValidationKey' => 'e10adc3949ba59abbe56e057f20f883e',
            'class'               => 'common\web\Request',
        ],
        'response'     => [
            'class'      => 'common\web\Response',
            'formatters' => [
                'jsonObject' => [
                    'class'         => 'yii\web\JsonResponseFormatter',
                    'encodeOptions' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT
                ]
            ]
        ],
        'errorHandler' => [
            'errorAction' => 'index/error'
        ],
        'urlManager'   => [
            'enablePrettyUrl' => true,
            'showScriptName'  => false,
            'rules'           => [
                'GET,POST,PUT,PATCH,DELETE /suite-agent-dev/receive/<corp_id:.*>' => 'suite-agent-dev/receive',
            ]
        ],
        'jwt'          => [
            'class'             => \sizeg\jwt\Jwt::class,
            'key'               => 'secret',
            'jwtValidationData' => \common\components\JwtValidationData::class,
        ],
        'user'         => [
            'identityClass' => Account::class,
        ],
    ],
    'params'              => $params
];