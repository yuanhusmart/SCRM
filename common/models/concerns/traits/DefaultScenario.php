<?php

namespace common\models\concerns\traits;

trait DefaultScenario
{
    public function scenarios()
    {
        return [
            'default' => array_keys($this->getAttributes())
        ];
    }
}