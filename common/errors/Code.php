<?php

namespace common\errors;

/**
 * Class Code
 * @package common\errors
 */
class Code
{
    // 成功
    const SUCCESS = '000000';

    // 授权|权限错误
    const UNAUTHORIZED                = '100000';
    const AUTHORIZATION_FAILED        = '100001';
    const LOGIN_TOKEN_OVERDUE         = '100002';
    const LOGIN_FAILED                = '100003';
    const REFRESH_ACCESS_TOKEN_FAILED = '100004';
    const NO_PERMISSION               = '100005';
    const SIGN_INVALID                = '100006';
    const SIGN_PARAMS_ERROR           = '100007';
    const APP_DOES_NOT_EXIST          = '100008';
    const SIGN_MISMATCH               = '100009';
    const USER_ID_TOO_MUCH            = '100010';
    const ACCESS_DENIED               = '100011';

    /**
     * @Message("没有该品牌权限")
     */
    const PERMISSION_DENIED = 10004;

    /**
     * @Message("业务处理错误")
     */
    const BUSINESS_UNABLE_PROCESS = 10001;

    //业务调用系列（2系列）
    const ERROR_CALL             = '200000';
    const BUSINESS_UNABLE_HANDLE = '200001';
    const BUSINESS_APP_NOT_EXIST = '200002';
    const BUSINESS_NOT_OPEN      = '200003';
    const NOT_CALL_BUSINESS      = '200004';

    //业务调用系列（3系列）
    const BUSINESS_ABNORMAL  = '300000';
    const DATABASE_EXCEPTION = '300001';
    const NOT_EXIST          = '300002';
    const CALL_EXCEPTION     = '300003';

    //业务调用系列（4系列）
    const WRONG_REQUEST               = '400000';
    const CLIENT_REQUESTS_NOT_ALLOWED = '400001';
    const PARAMS_ERROR                = '400002';
    const PARAMS_TYPE_ERROR           = '400003';
    const LACK_PARAMS                 = '400004';
    const CLIENTS_NOT_OPEN            = '400005';
    const UNKNOWN_CLIENTS             = '400006';
    const DATA_ERROR                  = '400007';
    const CREATE_ERROR                = '400008';
    const UPDATE_ERROR                = '400009';
    const DELETE_ERROR                = '400010';
    const VALIDATE_CODE_ERROR         = '400011';
    const CODE_SEND_ERROR             = '400012';
    const DUPLICATE_DATA              = '400013';
    const NOT_LOAN_ERROR              = '400014';

    //安全校验系列（5系列）
    const ILLEGAL_REQUEST   = '500000';
    const RESTRICTED_ACCESS = '500001';
    const SENSITIVE_INFO    = '500002';

    //服务系列系列（7系列）
    const SERVICE_INTERRUPTION = '700000';

    //网关系列系列（8系列）
    const BAD_GATEWAY = '800000';

    //系统级别类型系列（9系列）
    const SYSTEM_ERROR = '900000';
    const EXCEPTION    = '900001';
    const RUNTIME_ERROR    = '900002';

    //状态码信息
    const MESSAGE = [
        // 成功
        self::SUCCESS                     => ['status' => 200, 'message' => 'Successful'],

        // 授权|权限错误
        self::UNAUTHORIZED                => ['status' => 401, 'message' => '未授权'],
        self::AUTHORIZATION_FAILED        => ['status' => 401, 'message' => '未通过授权校验'],
        self::LOGIN_TOKEN_OVERDUE         => ['status' => 401, 'message' => '授权状态已过期'],
        self::LOGIN_FAILED                => ['status' => 401, 'message' => '登录失败'],
        self::REFRESH_ACCESS_TOKEN_FAILED => ['status' => 401, 'message' => '刷新令牌失败'],
        self::NO_PERMISSION               => ['status' => 403, 'message' => '没有权限'],
        self::SIGN_INVALID                => ['status' => 401, 'message' => '签名失效'],
        self::SIGN_PARAMS_ERROR           => ['status' => 400, 'message' => '签名参数错误'],
        self::APP_DOES_NOT_EXIST          => ['status' => 401, 'message' => '应用不存在'],
        self::SIGN_MISMATCH               => ['status' => 401, 'message' => '签名不匹配'],
        self::USER_ID_TOO_MUCH            => ['status' => 403, 'message' => '账号身份不明确'],
        self::ACCESS_DENIED               => ['status' => 403, 'message' => '无权访问'],

        // 业务调用系列（2系列）
        self::ERROR_CALL                  => ['status' => 400, 'message' => '错误的业务调用'],
        self::BUSINESS_UNABLE_HANDLE      => ['status' => 400, 'message' => '业务当前暂时无法处理请求'],
        self::BUSINESS_APP_NOT_EXIST      => ['status' => 401, 'message' => '业务应用不存在'],
        self::BUSINESS_NOT_OPEN           => ['status' => 401, 'message' => '业务未开放'],
        self::NOT_CALL_BUSINESS           => ['status' => 403, 'message' => '无权调用当前业务'],

        // 业务调用系列（3系列）
        self::BUSINESS_ABNORMAL           => ['status' => 400, 'message' => '业务处理异常'],
        self::DATABASE_EXCEPTION          => ['status' => 404, 'message' => '数据库操作异常'],
        self::NOT_EXIST                   => ['status' => 400, 'message' => '数据不存在'],
        self::CALL_EXCEPTION              => ['status' => 403, 'message' => '第三方业务调用异常'],

        // 业务调用系列（4系列）
        self::WRONG_REQUEST               => ['status' => 400, 'message' => '错误请求'],
        self::CLIENT_REQUESTS_NOT_ALLOWED => ['status' => 400, 'message' => '不允许的客户端请求'],
        self::PARAMS_ERROR                => ['status' => 400, 'message' => '参数值错误'],
        self::PARAMS_TYPE_ERROR           => ['status' => 403, 'message' => '参数类型错误'],
        self::LACK_PARAMS                 => ['status' => 403, 'message' => '缺少参数'],
        self::CLIENTS_NOT_OPEN            => ['status' => 403, 'message' => '未开放的客户端'],
        self::UNKNOWN_CLIENTS             => ['status' => 403, 'message' => '未知客户端'],
        self::DATA_ERROR                  => ['status' => 400, 'message' => '数据错误'],
        self::CREATE_ERROR                => ['status' => 400, 'message' => '创建失败'],
        self::UPDATE_ERROR                => ['status' => 400, 'message' => '更新失败'],
        self::DELETE_ERROR                => ['status' => 400, 'message' => '删除失败'],
        self::VALIDATE_CODE_ERROR         => ['status' => 400, 'message' => '验证码错误'],
        self::CODE_SEND_ERROR             => ['status' => 404, 'message' => '验证码下发失败'],
        self::DUPLICATE_DATA              => ['status' => 400, 'message' => '重复数据'],
        self::BUSINESS_UNABLE_PROCESS     => ['status' => 400, 'message' => '业务处理错误'],
        self::PERMISSION_DENIED           => ['status' => 400, 'message' => '没有该品牌权限'],
        self::NOT_LOAN_ERROR              => ['status' => 400, 'message' => '打款记录不存在'],

        //安全校验系列（5系列）
        self::ILLEGAL_REQUEST             => ['status' => 403, 'message' => '非法请求，已记录ip'],
        self::RESTRICTED_ACCESS           => ['status' => 403, 'message' => '已被限制访问'],
        self::SENSITIVE_INFO              => ['status' => 403, 'message' => '发现敏感信息'],

        //服务系列系列（7系列）
        self::SERVICE_INTERRUPTION        => ['status' => 500, 'message' => '服务已中断'],

        //网关系列系列（8系列）
        self::BAD_GATEWAY                 => ['status' => 500, 'message' => '网关错误'],

        //系统级别类型系列（9系列）
        self::SYSTEM_ERROR                => ['status' => 500, 'message' => '系统错误'],
        self::EXCEPTION                   => ['status' => 500, 'message' => '系统内部错误']
    ];

    /**
     * @return array
     */
    public static function statusMessages()
    {
        return self::MESSAGE;
    }

}