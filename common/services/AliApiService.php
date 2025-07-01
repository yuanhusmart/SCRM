<?php

namespace common\services;

use Yii;
use common\errors\Code;
use common\errors\ErrException;

/**
 * Class AliApiService
 * @package common\services
 */
class AliApiService extends Service
{
    /** @var string 银行卡二三四元素实名认证V2 */
    const BANK_TWO_ELEMENTS = '/v2/bcheck';

    /**
     * 身份证实名认证(二要素)
     * https://market.aliyun.com/products/57000002/cmapi022049.html?spm=5176.730005.productlist.d_cmapi022049.qjIYiv#sku=yuncode1604900000
     * @param $id_card string 身份证号码
     * @param $name string 真实姓名
     * 01    实名认证通过！    实名认证通过！
     * 02    实名认证不通过！    实名认证不通过！
     * 202    无法验证！    无法验证！
     * 203    异常情况！    异常情况！
     * 204    姓名格式不正确！    姓名格式不正确！
     * 205    身份证格式不正确！    身份证格式不正确！
     * 注意事项：
     * 出现'无法验证'时，表示‘库无’，原因如下：
     * (1) 现役军人，刚退役不到2年的军人（一般为2年）、特殊部门人员；
     * (2) 身份真实，大学生户口迁移；
     * (3) 户口迁出，且没有在新的迁入地迁入；
     * (4) 户口迁入新迁入地，当地公安系统未上报到公安部（上报时间有地域差异）；
     * (5) 更改姓名，当地公安系统未上报到公安部（上报时间有地域差异）；
     * (6) 身份真实，但是逾期未办理；
     * (7) 身份真实，未更换二代身份证；
     * (8) 移民和死亡；
     * (9) 身份证号确实不存在。
     * 本接口实时直连，库无的身份信息一般为真实信息，但身份信息目前的状态不对，您可根据您的业务灵活处理。
     * 建议和用户确认下是否变更过户籍信息或者让用户持身份证去当地派出所确认一下。
     * 您也可以加下我们客服，将协助您排查。
     * @param $params
     * @return mixed
     * @throws ErrException
     */
    public static function idCardCheck($params)
    {
        $attributes = self::includeKeys($params, ['name', 'idCard']);
        if (empty($attributes['name']) || empty($attributes['idCard'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . Yii::$app->params['ali']['app.code']);
        ksort($attributes);
        $url      = Yii::$app->params['ali']['id.card.url'] . "?" . http_build_query($attributes);
        $respJson = sendCurl($url, "GET", [], $headers);
        if (empty($respJson)) {
            Yii::warning($respJson);
            throw new ErrException(Code::CALL_EXCEPTION);
        }
        return json_decode($respJson, true);
    }

    /**
     * 阿里云-银行卡三要素认证调用接口
     * https://market.aliyun.com/products/57000002/cmapi028251.html?spm=5176.2020520132.101.12.19bc7218UvaAmG#sku=yuncode2225100000
     * @param $bank_number string 银行卡号
     * @param $id_card string 身份证号码
     * @param $name string 真实姓名
     * @return array
     * @throws \Exception
     */
    public static function bankThreeCheck($params)
    {
        $attributes = self::includeKeys($params, ['name', 'idCard', 'accountNo']);
        if (empty($attributes['name']) || empty($attributes['accountNo']) || empty($attributes['idCard'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . Yii::$app->params['ali']['app.code']);
        ksort($attributes);
        $url      = Yii::$app->params['ali']['bank.3check.url'] . "?" . http_build_query($attributes);
        $respJson = sendCurl($url, "GET", [], $headers);
        if (empty($respJson)) {
            Yii::warning($respJson);
            throw new ErrException(Code::CALL_EXCEPTION);
        }
        return json_decode($respJson, true);
    }

    /**
     * 阿里云-全国快递物流查询-快递查询接口
     * https://market.aliyun.com/products/57126001/cmapi021863.html?spm=5176.2020520132.101.2.28987218bfIHFd#sku=yuncode1586300000
     * @param $params
     * @param $params [$no] string 快递单号 【顺丰和丰网请输入单号 : 收件人或寄件人手机号后四位。例如：123456789:1234】
     * @param $params [$type] string 快递公司字母简写：不知道可不填 95%能自动识别，填写查询速度会更快【见产品详情】
     * @return mixed
     * @throws ErrException
     */
    public static function logisticsKdi($params)
    {
        $attributes = self::includeKeys($params, ['no', 'type']);
        if (empty($attributes['no'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . Yii::$app->params['ali']['logistics.app.code']);
        ksort($attributes);
        $url      = Yii::$app->params['ali']['logistics.url'] . "/kdi?" . http_build_query($attributes);
        $respJson = sendCurl($url, "GET", [], $headers);
        if (empty($respJson)) {
            Yii::warning($respJson);
            throw new ErrException(Code::CALL_EXCEPTION);
        }
        return json_decode($respJson, true);
    }

    /**
     * 阿里云-全国快递物流查询-单号识别快递公司
     * https://market.aliyun.com/products/57126001/cmapi021863.html?spm=5176.2020520132.101.2.28987218bfIHFd#sku=yuncode1586300000
     * @param $no string 快递单号 【顺丰和丰网请输入单号 : 收件人或寄件人手机号后四位。例如：123456789:1234】
     * @return array
     * @throws \Exception
     */
    public static function logisticsExCompany($params)
    {
        $no = self::getString($params, 'no');
        if (empty($no)) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . Yii::$app->params['ali']['logistics.app.code']);
        $url      = Yii::$app->params['ali']['logistics.url'] . "/exCompany?" . http_build_query(['no' => $no]);
        $respJson = sendCurl($url, "GET", [], $headers);
        if (empty($respJson)) {
            Yii::warning($respJson);
            throw new ErrException(Code::CALL_EXCEPTION);
        }
        return json_decode($respJson, true);
    }

    /**
     * 阿里云-全国快递物流查询-获取快递公司名称
     * https://market.aliyun.com/products/57126001/cmapi021863.html?spm=5176.2020520132.101.2.28987218bfIHFd#sku=yuncode1586300000
     * @param $type string 快递编码 或 不填写获取列表
     * @return array
     * @throws \Exception
     */
    public static function logisticsGetExpressList($params)
    {
        $attributes = self::includeKeys($params, ['type']);
        $headers    = array();
        array_push($headers, "Authorization:APPCODE " . Yii::$app->params['ali']['logistics.app.code']);
        $url      = Yii::$app->params['ali']['logistics.url'] . "/getExpressList?" . http_build_query($attributes);
        $respJson = sendCurl($url, "GET", [], $headers);
        if (empty($respJson)) {
            Yii::warning($respJson);
            throw new ErrException(Code::CALL_EXCEPTION);
        }
        return json_decode($respJson, true);
    }

    /**
     * 运营商三要素验证
     * https://market.aliyun.com/products/57000002/cmapi026100.html?spm=5176.730005.result.34.1a323524igNV45&innerSource=search_%E8%BA%AB%E4%BB%BD%E8%AF%81%E4%B8%89%E8%A6%81%E7%B4%A0#sku=yuncode2010000006
     * @param $params ['idcard'] string 身份证号码
     * @param $params ['name'] string 真实姓名
     * @param $params ['mobile'] string 手机号
     * @param $params
     * @return mixed
     * @throws ErrException
     * response:{
         * "code" : "0",
         * "message" : "成功",
         * "result" : {
             * "name" : "冯一枫", //姓名
             * "mobile" : "18011223678", //手机号
             * "idcard" : "350301191212222329422", //身份证号
             * "res" : "1", //验证结果   1:一致  2:不一致  3:无记录  -1:异常
             * "description" : "一致",   // 验证结果状态描述（与res状态码相对应）
             * "sex": "男",
             * "birthday": "19930123",
             * "address": "江西省遂川县"
         * }
     * }
     */
    public static function mobileVerifyRealName($params)
    {
        $attributes = self::includeKeys($params, ['name', 'idcard', 'mobile']);
        if (empty($attributes['name']) || empty($attributes['idcard']) || empty($attributes['mobile'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . Yii::$app->params['ali']['app.code']);
        //根据API的要求，定义相对应的Content-Type
        array_push($headers, "Content-Type:application/x-www-form-urlencoded; charset=UTF-8");
        ksort($attributes);
        $url      = "https://mobile3elements.shumaidata.com/mobile/verify_real_name";
        $respJson = sendCurl($url, "POST", http_build_query($attributes), $headers);
        Yii::warning("【运营商三要素验证】-url:{$url} -body:" . http_build_query($attributes) . ' -response:' . $respJson);
        if (empty($respJson)) {
            Yii::warning($respJson);
            throw new ErrException(Code::CALL_EXCEPTION);
        }
        return json_decode($respJson, true);
    }

    /**
     * 银行卡二三四元素实名认证V2
     * https://market.aliyun.com/products/57000002/cmapi00037886.html?spm=5176.730005.result.5.25543524ApNPl3&innerSource=search_%E9%93%B6%E8%A1%8C%E4%BA%8C%E8%A6%81%E7%B4%A0#sku=yuncode3188600001
     * @param $params ['accountNo'] string 银行卡卡号
     * @param $params ['name'] string 持卡人姓名
     * @param $params ['bankPreMobile'] string 银行预留手机号码（四要素认证必填） [可选]
     * @param $params ['idCardCode'] string 身份证号码（三要素认证必填） [可选]
     * @param $params
     * @return mixed
     * @throws ErrException
     * {
     *   "error_code": 0,
     *   "reason": "成功",
     *   "result": {
     *     "respCode": "F",
     *     "respMsg": "验证要素格式有误",
     *     "detailCode": "12",
     *     "bancardInfor": {
     *       "bankName": "农业银行",
     *       "BankId": 3,
     *       "type": "借记卡",
     *       "cardname": "金穗通宝卡(银联卡)",
     *       "tel": "95599",
     *       "Icon": "nongyeyinhang.gif"
     *     }
     *   }
     * }
     */
    public static function bCheckV2($params)
    {
        $attributes = self::includeKeys($params, ['accountNo', 'bankPreMobile', 'idCardCode', 'name']);
        if (empty($attributes['name']) || empty($attributes['accountNo'])) {
            throw new ErrException(Code::PARAMS_ERROR);
        }
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . Yii::$app->params['ali']['app.code']);
        //根据API的要求，定义相对应的Content-Type
        array_push($headers, "Content-Type:application/x-www-form-urlencoded; charset=UTF-8");
        ksort($attributes);
        $url      = env('BANK_TWO_ELEMENTS_QUERY') . self::BANK_TWO_ELEMENTS;
        $respJson = sendCurl($url, "POST", http_build_query($attributes), $headers);
        Yii::warning("【银行卡二三四元素实名认证V2】-url:{$url} -body:" . http_build_query($attributes) . ' -response:' . $respJson);
        if (empty($respJson)) {
            Yii::warning($respJson);
            throw new ErrException(Code::CALL_EXCEPTION);
        }
        return json_decode($respJson, true);
    }

    /**
     * 获取银行卡天眼数据
     * @param $params
     * @return mixed
     * @author 龚德铭
     * @date 2023/4/11 9:08
     * https://market.aliyun.com/products/57002003/cmapi00036032.html?spm=5176.2020520132.101.3.467272184zvxvs#sku=yuncode3003200001
     */
    public static function getBankTianyanData($params)
    {
        $bankCard = self::getString($params, 'bank_card');
        if (!$bankCard) {
            throw new ErrException(Code::PARAMS_ERROR, '支行联行号未定义');
        }

        $requestParams = [
            'bankcard' => $bankCard,
        ];

        $headers  = [
            'Authorization:APPCODE ' . env('ALI_APP_CODE')
        ];
        $url      = env('BANK_BANK_TIANYAN_INFO_QUERY') . '?' . http_build_query($requestParams);
        $response = sendCurl($url, 'GET', [], $headers);
        Yii::warning("【获取银行卡天眼数据】params: " . json_encode($requestParams, JSON_UNESCAPED_UNICODE) . ' response: ' . $response);
//        $response = '{"msg":"成功","success":true,"code":200,"data":{"order_no":"423667061740468868","bank":"工商银行","province":"四川","city":"成都","card_name":"牡丹卡普卡","tel":"95588","type":"借记卡","logo":"https://img.tianyandata.cn/billing/bank_info/202304/20230411/1681175627808.jpg","abbreviation":"ICBC","card_bin":"621226","bin_digits":6,"card_digits":19,"isLuhn":true,"weburl":"www.icbc.com.cn"}}';
        if (empty($response)) {
            throw new ErrException(Code::CALL_EXCEPTION);
        }
        return json_decode($response, true);
    }

}
