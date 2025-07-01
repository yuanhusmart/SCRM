<?php

namespace common\components;

use common\db\concerns\Paginator;
use common\errors\Code;
use common\helpers\ExceptionResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Throwable;
use Yii;
use yii\helpers\Json;
use yii\web\Controller;
use yii\web\Request;
use yii\web\Response;
use yii\filters\Cors;
use yii\helpers\ArrayHelper;

/**
 * 所有控制器的基类
 * Class BaseController
 * @package common\components
 */
class BaseController extends Controller
{
    protected $encrypt;

    /**
     * @var Request
     */
    public $request;

    /**
     * @var Response
     */
    public $response;

    /**
     * 跨域设置
     * @return array
     */
    public function behaviors()
    {
        return ArrayHelper::merge([
            [
                'class' => Cors::className(),
                'cors'  => [
                    'Origin'                        => ['*'],
                    'Access-Control-Request-Method' => ['GET', 'HEAD', 'OPTIONS', 'POST'],
                    'Access-Control-Allow-Headers'  => ['Origin', 'X-Requested-With', 'Content-Type', 'Accept', 'Authorization', 'Referer', 'User-Agent', 'token', 'sign', 'encrypt']
                ],
            ],
        ], parent::behaviors());
    }

    /**
     * 初始化
     * @throws \Exception
     */
    public function init()
    {
        if (empty($this->encrypt)) {
            $this->encrypt = Yii::$app->request->headers->get('encrypt', env('IS_ENCRYPT'));
        }
        parent::init();
        $this->request  = Yii::$app->request;
        $this->response = Yii::$app->response;
    }

    /**
     * 获取 Body 参数，并按 json 格式解析为数组
     * @return array
     */
    public function getBodyParams()
    {
        $input  = file_get_contents('php://input');
        $params = json_decode($input, true);
        if (!is_array($params)) {
            $params = array();
        }
        return $params;
    }

    /**
     * 获取参数
     * @param $key
     * @param $default
     * @return mixed|null
     */
    public function input($key = null, $default = null)
    {
        return input($key, $default);
    }

    /**
     * 获取所有参数
     * @return array
     */
    public function params()
    {
        return input();
    }

    /**
     * 获取指定参数
     * @param $keys
     * @return array
     */
    public function only($keys)
    {
        return Arr::only($this->input(), $keys);
    }

    /**
     * 获取 Body 参数，并按 xml 格式解析为数组
     * @return array
     */
    public function getBodyXmlParams()
    {
        $input  = file_get_contents('php://input');
        $xml    = simplexml_load_string($input, 'SimpleXMLElement', LIBXML_NOCDATA);
        $params = json_decode(json_encode($xml), true);
        if (!is_array($params)) {
            $params = array();
        }
        return $params;
    }

    /**
     * @return array
     */
    public function getQueryParams()
    {
        return Yii::$app->request->getQueryParams();
    }

    /**
     * @return array|mixed
     */
    public function postParams()
    {
        return Yii::$app->request->post();

    }

    /**
     * 响应一个错误
     * @param string $code
     * @param string $message
     * @param int $status
     * @return Response
     */
    public function responseError($code, $message, $status = 500)
    {
        if (empty(Code::MESSAGE[$code]) && empty(Code::MESSAGE[$code])) {
            $status = 500;
        }
        $this->response->data = [
            'code'    => strval($code),
            'message' => $message,
            'data'    => null
        ];
        if (!in_array($code, [Code::UNAUTHORIZED, Code::NOT_EXIST])) {
            Yii::error($this->response->data);
        }
        $this->response->format     = Response::FORMAT_JSON;
        $this->response->statusCode = $status;
        return $this->response;
    }

    /**
     * 响应一个成功的数据
     * @param mixed|null $data
     * @param string $format
     * @param string $message
     * @return Response
     */
    public function responseSuccess($data = null, $format = Response::FORMAT_JSON, $message = 'Successful')
    {
        if ($this->encrypt !== 'false') {
            $data = $this->responseEncrypt($data);
            $this->response->headers->add('encrypt', $this->encrypt);
            Yii::$app->response->headers->set('Access-Control-Expose-Headers', 'encrypt');
            Yii::$app->response->headers->set('encrypt', $this->encrypt);
        }
        $this->response->data   = [
            'code'    => Code::SUCCESS,
            'message' => $message,
            'data'    => $data
        ];
        $this->response->format = $format;
        return $this->response;
    }

    /**
     * 加密返回数据
     * @param $data
     * @return string
     */
    protected function responseEncrypt($data)
    {
        $data = Json::encode($data, JSON_UNESCAPED_UNICODE);
        // 加密方式
        $method = 'aes-256-cbc';
        // 密钥
        $key = env('ENCRYPT_KEY');
        // 随机生成初始化向量
        $iv        = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
        $encrypted = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);
        // 将加密数据和初始化向量进行编码
        $encrypted = base64_encode($encrypted . $iv);
        return $encrypted;
    }

    /**
     * 解密数据
     * @param $data
     * @return false|string
     */
    protected function decrypt($data)
    {
        // 密钥
        $key = env('AES_KEY');
        // 加密方式
        $method   = 'aes-256-cbc';
        $decoded  = base64_decode($data);
        $ivLength = openssl_cipher_iv_length($method);
        $iv       = substr($decoded, -$ivLength);
        return openssl_decrypt(substr($decoded, 0, -$ivLength), $method, $key, OPENSSL_RAW_DATA, $iv);
    }

    public function responsePaginator(Paginator $paginator, $transformer = null, $meta = [])
    {
        $content = $paginator->getItems();

        if ($transformer && method_exists($transformer, 'transform')) {
            $content = array_map(function ($item) use ($transformer) {
                return $transformer->transform($item);
            }, $content);
        }

        return $this->responseSuccess(array_merge([
            'list'       => $content,
            'pagination' => [
                'total'        => (int)$paginator->getTotal(),
                'per_page'     => (int)$paginator->getPerPage(),
                'current_page' => (int)$paginator->getCurrentPage(),
            ]
        ], $meta));
    }

    /**
     * @param array|Collection $collection
     * @param object $transformer
     * @param $meta
     * @return Response
     */
    public function responseCollection($collection, $transformer = null, $meta = [])
    {
        $content = $collection;

        if ($transformer && method_exists($transformer, 'transform')) {
            $content = array_map(function ($item) use ($transformer) {
                return $transformer->transform($item);
            }, $content);
        }

        return $this->responseSuccess(array_merge([
            'list' => $content,
        ], $meta));
    }

    public function responseItem($item, $transformer = null)
    {
        if ($transformer && method_exists($transformer, 'transform')) {
            $item = $transformer->transform($item);
        }

        return $this->responseSuccess([
            'item' => $item,
        ]);
    }

    public function responseThrow(?Throwable $e, $code = Code::RUNTIME_ERROR, $message = null)
    {
        $response = new ExceptionResponse();
        $response->code($code);
        $response->exception($e);
        $response->content($message);

        return $response;
    }
}