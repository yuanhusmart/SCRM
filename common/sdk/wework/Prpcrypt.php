<?php

namespace common\sdk\wework;

use common\sdk\wework\ErrorCode;
use common\services\Service;

/**
 * Prpcrypt class
 *
 * 提供接收和推送给公众平台消息的加解密接口.
 */
class Prpcrypt
{
    public $key = null;
    public $iv  = null;

    /**
     * Prpcrypt constructor.
     * @param $k
     */
    public function __construct($k = '')
    {
        if (!$k) {
            $k = \Yii::$app->params['workWechat']['callbackEncodingAESKey'];
        }
        $this->key = base64_decode($k . '=');
        $this->iv  = substr($this->key, 0, 16);
    }

    /**
     * 加密
     *
     * @param $text
     * @param $receiveId
     * @return array
     */
    public function encrypt($text, $receiveId)
    {
        try {
            //拼接
            $text = $this->getRandomStr() . pack('N', strlen($text)) . $text . $receiveId;
            //添加PKCS#7填充
            $pkc_encoder = new PKCS7Encoder();
            $text        = $pkc_encoder->encode($text);
            //加密
            if (function_exists('openssl_encrypt')) {
                $encrypted = openssl_encrypt($text, 'AES-256-CBC', $this->key, OPENSSL_ZERO_PADDING, $this->iv);
            } else {
                $encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->key, base64_decode($text), MCRYPT_MODE_CBC, $this->iv);
            }
            return array(ErrorCode::$OK, $encrypted);
        } catch (\Exception $e) {
            \Yii::warning($e);
            return array(ErrorCode::$EncryptAESError, null);
        }
    }

    /**
     * 解密
     *
     * @param $encrypted
     * @param $receiveId
     * @return array
     */
    public function decrypt($encrypted, $receiveId)
    {
        try {
            //解密
            if (function_exists('openssl_decrypt')) {
                $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $this->key, OPENSSL_ZERO_PADDING, $this->iv);
            } else {
                $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->key, base64_decode($encrypted), MCRYPT_MODE_CBC, $this->iv);
            }
        } catch (\Exception $e) {
            return array(ErrorCode::$DecryptAESError, null);
        }
        try {
            //删除PKCS#7填充
            $pkc_encoder = new PKCS7Encoder();
            $result      = $pkc_encoder->decode($decrypted);
            if (strlen($result) < 16) {
                return array();
            }
            //拆分
            $content        = substr($result, 16, strlen($result));
            $len_list       = unpack('N', substr($content, 0, 4));
            $xml_len        = $len_list[1];
            $xml_content    = substr($content, 4, $xml_len);
            $from_receiveId = substr($content, $xml_len + 4);
            \Yii::warning('Prpcrypt receiveId decrypt:' . $from_receiveId);
            \Yii::warning('Prpcrypt content decrypt:' . $xml_content);
        } catch (\Exception $e) {
            \Yii::warning($e);
            return array(ErrorCode::$IllegalBuffer, null);
        }
        if ($from_receiveId != $receiveId) {
            return array(ErrorCode::$ValidateCorpidError, null);
        }
        return array(0, $xml_content);
    }

    /**
     * @param $encrypted
     * @return array
     */
    public function getWorkWechatMsgDecrypt($encrypted)
    {
        try {
            //解密
            if (function_exists('openssl_decrypt')) {
                $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $this->key, OPENSSL_ZERO_PADDING, $this->iv);
            } else {
                $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->key, base64_decode($encrypted), MCRYPT_MODE_CBC, $this->iv);
            }
        } catch (\Exception $e) {
            return array(ErrorCode::$DecryptAESError, null);
        }
        try {
            //删除PKCS#7填充
            $pkc_encoder = new PKCS7Encoder();
            $result      = $pkc_encoder->decode($decrypted);
            if (strlen($result) < 16) {
                return array();
            }
            //拆分
            $content        = substr($result, 16, strlen($result));
            $len_list       = unpack('N', substr($content, 0, 4));
            $xml_len        = $len_list[1];
            $msg            = substr($content, 4, $xml_len);
            $from_receiveId = substr($content, $xml_len + 4);
        } catch (\Exception $e) {
            \Yii::warning($e);
        }

        if (!empty($msg)) {
            if (Service::isXml($msg)) {
                $xml = simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA);
                $msg = json_decode(json_encode($xml), true);
            }
        }

        return [
            'msgLen'    => $xml_len ?? 0,
            'msg'       => $msg ?? '',
            'receiveId' => $from_receiveId ?? ''
        ];
    }

    /**
     * 生成随机字符串
     *
     * @return string
     */
    public function getRandomStr(): string
    {
        $str     = '';
        $str_pol = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyl';
        $max     = strlen($str_pol) - 1;
        for ($i = 0; $i < 16; $i++) {
            $str .= $str_pol[mt_rand(0, $max)];
        }
        return $str;
    }
}
