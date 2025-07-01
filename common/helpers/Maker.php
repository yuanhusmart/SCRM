<?php

namespace common\helpers;

trait Maker
{
    /**
     * create instance
     * @return static
     */
    public static function make()
    {
        return new static(...func_get_args());
    }
}
