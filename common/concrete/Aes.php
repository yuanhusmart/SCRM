<?php

namespace common\concrete;

/**
 * @desc：php aes加密解密类
 */
class Aes
{
    /**
     * CI_Aes cipher
     * @var string
     */
    protected $cipher;
    protected $iv;
    protected $options;
    /**
     * CI_Aes key
     *
     * @var string
     */
    protected $key;

    /**
     * CI_Aes constructor
     *
     * options = 0: 默认模式，自动对明文进行 pkcs7 padding，且数据做 base64 编码处理。
     * options = 1: OPENSSL_RAW_DATA，自动对明文进行 pkcs7 padding， 且数据未经 base64 编码处理。
     * options = 2: OPENSSL_ZERO_PADDING，要求待加密的数据长度已按 “0” 填充与加密算法数据块长度对齐
     *
     * @param string $key Configuration parameter
     */

    public function __construct($key = null, $cipher = 'aes-128-ecb', $iv = '', $options = 0)
    {
        $this->key     = $key;
        $this->cipher  = $cipher;
        $this->iv      = $iv;
        $this->options = $options;
    }

    /**
     * Encrypt
     *
     * @param string $data Input data
     * @return string
     */
    public function encrypt($data)
    {
        $endata = openssl_encrypt($data, $this->cipher, $this->key, $this->options, $this->iv);
        return $endata;
    }

    /**
     * Decrypt
     *
     * @param string $data Encrypted data
     * @return string
     */
    public function decrypt($data)
    {
        $encrypted = $data;
        return openssl_decrypt($encrypted, $this->cipher, $this->key, $this->options, $this->iv);
    }
}
