<?php
return [
    'appUrl'      => env('APP_URL'),
    'appName'     => env('APP_NAME','ai-analysis-work-wechat'),
    'redisPrefix' => 'aaw.',
    // 阿里云
    'ali'         => [
        // 表格存储
        'ots' => [
            'accessKeyId'     => env('OTS_ACCESS_KEY_ID'),
            'accessKeySecret' => env('OTS_ACCESS_KEY_SECRET'),
            'endpoint'        => env('OTS_ENDPOINT'),
            'instanceName'    => env('OTS_INSTANCE_NAME'),
        ]
    ],
    'jwtSchema'   => 'Bearer',
    // 企业微信
    'workWechat'  => [
        # 企业微信 第三方应用 ID
        'suiteId'                => env("WORK_WECHAT_SUITE_ID"),
        'appName'                => env('WORK_WECHAT_APPNAME'), // 第三方应用名字，与服务商应用保持相同
        'suiteName'              => env("WECHAT_SUITE_NAME"),
        # 企业微信 第三方应用密钥
        'suiteSecret'            => env("WORK_WECHAT_SUITE_SECRET"),
        # 企业微信 第三方应用 回调配置 Token
        'callbackToken'          => env("WORK_WECHAT_CALLBACK_TOKEN"),
        # 企业微信 第三方应用 回调配置 EncodingAESKey
        'callbackEncodingAESKey' => env("WORK_WECHAT_CALLBACK_ENCODING_AES_KEY"),
        # 服务商 通用开发参数 企业ID
        'corpId'                 => env("SUITE_CORP_ID"),
        # 鱼爪网服务商 通用开发参数 ProviderSecret
        'suiteProviderSecret'    => env("SUITE_PROVIDER_SECRET"),
        # 鱼爪网登录授权服务商ID
        'loginAuthSuiteId'       => env("WECHAT_LOGIN_AUTH_SUITE_ID"),
        # 鱼爪网登录授权服务商Secret
        'loginAuthSuiteSecret'   => env("WECHAT_LOGIN_AUTH_SUITE_SECRET"),
        # 服务商 ID Secret 对应关系
        'suiteRelation'          => [
            # 应用 ID 与 Secret
            env("WORK_WECHAT_SUITE_ID")       => env("WORK_WECHAT_SUITE_SECRET"),
            # 登录授权 ID 与 Secret
            env("WECHAT_LOGIN_AUTH_SUITE_ID") => env("WECHAT_LOGIN_AUTH_SUITE_SECRET"),
        ],
        # 消息加密公钥
        'publicKey'              => env("WORK_WECHAT_PUBLIC_KEY"),
        # 消息加密私钥
        'privateKey'             => env("WORK_WECHAT_PRIVATE_KEY"),
        // 专区程序ID
        'programId' => env('WORK_WECHAT_PROGRAM_ID')
    ],
    'cacheTime'   => 1800
];