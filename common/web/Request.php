<?php

namespace common\web;

use Yii;

/**
 * 自定义扩展 Request 组件
 * Class Request
 * @package common\web
 */
class Request extends \yii\web\Request
{

    public $enableCsrfValidation = false;

    public $jaegerSpan = null;

    public $jaegerRootSpan = null;

    /**
     * Request 组件初始化，处理 OPTIONS 请求
     */
    public function init()
    {
        parent::init();
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Origin,X-Requested-With,Content-Type,Accept,Authorization,Referer,User-Agent,Token,access_token,sign,encrypt');
        header('Access-Control-Allow-Methods: GET,POST,PUT,PATCH,DELETE,OPTIONS');
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit();
        }
        $this->responseAssets();
    }

    /**
     * 响应静态文件
     */
    public function responseAssets()
    {
        if (!isset($_SERVER['REQUEST_URI'])) {
            return;
        }

        $uri = $_SERVER['REQUEST_URI'];
        if ($uri === "/WW_verify_qKQIKbhDCIExZcqz.txt") {
            $root_path = $_SERVER['DOCUMENT_ROOT'];
            $file      = $root_path . $uri;
            echo file_get_contents($file);
            exit();
        }

        if (!(in_array(substr($uri, 0, 8), ['/assets/', '/static/']) || in_array(substr($uri, 0, 13), ['/attachments/']))) {
            return;
        }

        $file = Yii::$app->basePath . '/web' . $uri;
        if (!is_file($file)) {
            return;
        }
        $fileinfo      = explode('.', $file);
        $ext           = strtolower(end($fileinfo));
        $content_types = [
            'css' => 'text/css',
            'js'  => 'application/javascript',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'bmp' => 'image/bmp',
            'gif' => 'image/gif'
        ];
        $content_type  = '';
        if (isset($content_types[$ext])) {
            $content_type = $content_types[$ext];
        }
        header('HTTP/1.1 200 OK');
        header('Status: 200 OK');
        if ($content_type) {
            header('Content-Type: ' . $content_type);
        }
        echo file_get_contents($file);
        exit();
    }

}