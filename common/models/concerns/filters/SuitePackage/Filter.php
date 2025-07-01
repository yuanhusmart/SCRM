<?php

namespace common\models\concerns\filters\SuitePackage;

use common\models\concerns\filters\QueryFilter;

class Filter extends QueryFilter
{
    public function id($value)
    {
        if ($value) {
            $this->query->andWhere(['id' => $value]);
        }
    }

    // status
    public function status($value)
    {
        if ($value) {
            $this->query->andWhere(['status' => $value]);
        }
    }

    public function type($value)
    {
        if ($value) {
            $this->query->andWhere(['type' => $value]);
        }
    }

    // name
    public function name($value)
    {
        if ($value) {
            $this->query->andWhere(['like', 'name', $value]);
        }
    }
}

