<?php

namespace common\helpers\validator;


class ValidationException extends \Exception
{
    public $statusCode = 422;
    public $errors = [];

    public function __construct($message = "", $errors = [])
    {
        parent::__construct($message, 422000);

        $this->errors = $errors;
    }


    public function errors()
    {
        return $this->errors;
    }
}