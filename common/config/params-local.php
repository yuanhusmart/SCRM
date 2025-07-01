<?php
return [
    'APP_NAME'    => env("APP_NAME"),
    // 阿里oss对象存储
    'oss'         => [
        'default'          => env("OSS_DEFAULT"), // 你的域名
        'accessKeyId'      => env("OSS_ACCESS_KEY_ID"),
        'accessKeySecret'  => env("OSS_ACCESS_KEY_SECRET"),
        'bucket'           => env("OSS_BUCKET"),                                      // 存储空间名称
        'endpoint'         => env("OSS_ENDPOINT"),                                    // Endpoint以杭州为例，其它Region请按实际情况填写。
        'interEndpoint'    => env("OSS_INTER_ENDPOINT"),                              // 内网
        'localMappingPath' => env("OSS_LOCAL_MAPPING_PATH"),                          // OSS 映射本地地址
        'region'          => env("OSS_REGION", 'oss-cn-shanghai'),
        'sts'             => [
            'roleArn'         => env("OSS_STS_ROLE_ARN"),
            'roleSessionName' => env("OSS_STS_ROLE_SESSION_NAME"),
        ],
    ],
    // 业务id发号器配置
    'snowflake'   => [
        'worker_id'              => 1,//机器ID
        'server_id'              => 7,//数据产生节点，在数据库分离前暂做如下分配：1=支付中心，2=商品中心，3=订单中心，4=用户中心,5.工具箱,6.运营中心 7.财务中心 8-用户中心
        'workids_key'            => 'test',
        'workids_clean_lock_key' => 'test_a',
    ],
    'amqp'        => [
        'host'      => env("RABBITMQ_HOST"),
        'port'      => env("RABBITMQ_PORT", 5675),
        'login'     => env("RABBITMQ_LOGIN"),
        'password'  => env("RABBITMQ_PASSWORD"),
        'vhost'     => env("RABBITMQ_VHOST"),
        'heartbeat' => env("RABBITMQ_HEARTBEAT", 600),
        'exchange'  => [
            [
                'change'        => 'ai-analysis-work-wechat.msg.data.dir.ex',
                'queue'         => 'ai-analysis-work-wechat.msg.data.que',
                'x-message-ttl' => 2
            ]
        ]
    ],
    // 公共权限接口
    'PUBLIC_AUTH' => [
        'public/logout',
        'asi/get-contact-way-by-number',
    ]
];