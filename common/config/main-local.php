<?php

use common\db\RedisConnection;

return [
    'components' => [
        'db'      => [
            'class'      => 'yii\db\Connection',
            'dsn'        => sprintf('mysql:dbname=%s;host=%s;port=%d', env('MYSQL_DBNAME'), env('MYSQL_HOST'), env('MYSQL_PORT', 3306)),
            'username'   => env('MYSQL_USERNAME'),
            'password'   => env('MYSQL_PASSWORD'),
            'charset'    => env('MYSQL_CHARSET', 'utf8mb4')
        ],
        'cache'   => [
            'class'     => 'yii\redis\Cache',
            'redis'     => [
                'hostname' => env('REDIS_HOST', 'localhost'),
                'port'     => env('REDIS_PORT', 6379),
                'database' => env('REDIS_DATABASE', 1),
                'password' => env('REDIS_PASSWORD')
            ]
        ],
        'redis'   => [
            // 替换redis的类
            'class'    => RedisConnection::class,
            'hostname' => env('REDIS_HOST', 'localhost'),
            'port'     => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DATABASE', 1),
            'password' => env('REDIS_PASSWORD')
        ],
        'mongodb' => [
            'class' => '\yii\mongodb\Connection',
            'dsn'   => sprintf('mongodb://%s:%s@%s/%s', env('ORDER_MONGODB_USERNAME'), env('ORDER_MONGODB_PASSWORD'), env('ORDER_MONGODB_DSN'), env('ORDER_MONGODB_DBNAME'))
        ]
    ],
    'bootstrap' => ['gii'],
    'modules'    => [
        'gii' => [
            'class'      => 'yii\gii\Module',
            'allowedIPs' => ['*']
        ]
    ],
];