<?php

namespace common\models\concerns\filters\SuiteSpeech;

use common\models\concerns\filters\QueryFilter;

class Filter extends QueryFilter
{
    public $defaultFilters = [
        'suiteId',
        'corpId',
        'mine'
    ];

    public function suiteId()
    {
        $value = $this->data['suite_id'] ?? auth()->suiteId();

        if ($value) {
            $this->query->andWhere(['suite_id' => $value]);
        }
    }

    public function corpId()
    {
        $value = $this->data['corp_id'] ?? auth()->config()['corp_id'];

        if ($value) {
            $this->query->andWhere(['corp_id' => $value]);
        }
    }

    public function categoryId($value)
    {
        if ($value) {
            $this->query->andWhere(['category_id' => $value]);
        }
    }

    public function id($value)
    {
        if ($value) {
            $this->query->andWhere(['id' => $value]);
        }
    }

    public function name($value)
    {
        if ($value) {
            $this->query->andWhere(['like', 'name', $value]);
        }
    }

    public function type($value)
    {
        if ($value) {
            $this->query->andWhere(['type' => $value]);
        }
    }

    public function status($value)
    {
        if ($value) {
            $this->query->andWhere(['status' => $value]);
        }
    }

    // kind
    public function kind($value)
    {
        if ($value) {
            $this->query->andWhere(['kind' => $value]);
        }
    }

    // mine
    public function mine($value)
    {
        if ($value) {
            $this->query->andWhere([
                'OR',
                ['creator_id' => auth()->accountId()],
                ['kind' => 1]
            ]);
        }
    }
}

