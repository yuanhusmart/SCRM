<?php

use common\log\SqlTarget;
use yii\db\Command;
use yii\redis\Connection;

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php'
);
require dirname(__DIR__) . '/helpers/function.php';

// 替换mysql类
Yii::$container->set(Command::class, common\db\DbCommand::class);

return [
    'bootstrap'  => ['common\components\events\QueryRecord', 'common\components\events\EventTrigger'],
    'aliases'    => [
        '@bower' => '@vendor/yidas/yii2-bower-asset/bower',
        '@npm'   => '@vendor/npm-asset',
    ],
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'components' => [
        // 雪花算法，PHP-FPM 方式
        'request'   => [
            'cookieValidationKey' => false,
            'class'               => 'common\web\Request',
        ],
        'response'  => [
            'class'      => 'common\web\Response',
            'formatters' => [
                'jsonObject' => [
                    'class'         => 'yii\web\JsonResponseFormatter',
                    'encodeOptions' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT
                ]
            ]
        ],
        'snowFlake' => [
            'class'             => 'common\components\SnowFlake',
            'epochOffset'       => strtotime('2019-01-01 00:00:00') * 1000,
            'redisComponent'    => 'redis',
            'sequenceKey'       => 'snow_flake:sequence',
            'sequenceRedisIncr' => true,
            'sequenceExpire'    => 10
        ],
        'log'       => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets'    => [
                [
                    'class'          => 'yii\log\FileTarget',
                    'exportInterval' => 1,
                    'levels'         => ['error', 'warning'],
                    'logVars'        => [],
                    'logFile'        => '@runtime/logs/' . date('Ymd') . '/app.log'
                ],
                /** SQL日志 */
                [
                    'class'      => SqlTarget::class,
                    'levels'     => YII_DEBUG ? ['info', 'error', 'warning'] : ['error'],
                    'logVars'    => [],
                    'categories' => [
                        'yii\db\*',
                        'app\models\*'
                    ],
                    'logFile'    => '@runtime/logs/' . date('Ymd') . '/sql.log',
                ],
            ]
        ],
    ],
    "params"     => $params
];

