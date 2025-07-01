<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php'
);
return [
    'id' => 'console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'charset' => 'utf-8',
    'language' => 'zh-CN',
    'sourceLanguage' => 'en-US',
    'controllerNamespace' => 'console\controllers',
    'components' => [
        'request' => [
            'cookieValidationKey' => false,
            'class' => \common\web\ConsoleRequest::class
        ],
        'log'       => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'flushInterval' => 1,
            'targets'    => [
                [
                    'class'          => 'yii\log\FileTarget',
                    'levels'         => ['error', 'warning'],
                    'exportInterval' => 1,
                    'logVars'        => [],
                    'logFile'        => '@runtime/logs/' . date('Ymd') . '/app.log'
                ],
            ]
        ],
    ],
    'params' => $params,
];