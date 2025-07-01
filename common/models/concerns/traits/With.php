<?php

namespace common\models\concerns\traits;

trait With
{
    /**
     * 加载关联关系
     * @param string|array $with
     * @return void
     */
    public function with($with)
    {
        foreach ((array)$with as $relate) {
            $this->__get($relate);
        }
    }
}