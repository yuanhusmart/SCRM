<?php
namespace common\web;

use Yii;

/**
 * 自定义扩展 Request 组件
 * Class Request
 * @package common\web
 */
class ConsoleRequest extends \yii\console\Request
{

    public $enableCsrfValidation = false;

    public $cookieValidationKey = "";

    public $jaegerSpan = null;

    public $jaegerRootSpan = null;

}