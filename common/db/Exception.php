<?php

namespace common\db;

use Throwable;

class Exception extends \Exception
{

    public function __construct($message, $info, $code,Throwable $throwable) {
        parent::__construct($message, $code, null);
    }
}