<?php

namespace common\models\concerns\filters\SuitePermission;

use common\models\concerns\filters\QueryFilter;

class Filter extends QueryFilter
{
    public function id($value)
    {
        if ($value) {
            $this->query->andWhere(['id' => $value]);
        }
    }

    // parent_id
    public function parentId($value)
    {
        if ($value) {
            $this->query->andWhere(['parent_id' => $value]);
        }
    }

    public function name($value)
    {
        if ($value) {
            $this->query->andWhere(['like', 'name', $value]);
        }
    }

    public function slug($value)
    {
        if ($value) {
            $this->query->andWhere(['slug' => $value]);
        }
    }

    public function route($value)
    {
        if ($value) {
            $this->query->andWhere(['route' => $value]);
        }
    }

    public function level($value)
    {
        if ($value) {
            $this->query->andWhere(['level' => $value]);
        }
    }

    public function isHide($value)
    {
        if ($value) {
            $this->query->andWhere(['is_hide' => $value]);
        }
    }

    public function status($value)
    {
        if ($value) {
            $this->query->andWhere(['status' => $value]);
        }
    }

    // type
    public function type($value)
    {
        if ($value) {
            $this->query->andWhere(['type' => $value]);
        }
    }
}

