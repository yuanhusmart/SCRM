<?php

namespace common\models\concerns\filters\SuiteRole;

use common\models\concerns\filters\QueryFilter;

class Filter extends QueryFilter
{
    public function id($value)
    {
        if($value){
            $this->query->andWhere(['id' => $value]);
        }
    }

    public function name($value)
    {
        if($value){
            $this->query->andWhere(['like', 'name', $value]);
        }
    }

    public function kind($value)
    {
        if($value){
            $this->query->andWhere(['kind' => $value]);
        }
    }

    // corp_id
    public function corpId()
    {
        $value = $this->data['corp_id'] ?? auth()->config()['corp_id'];

        if($value){
            $this->query->andWhere(['corp_id' => $value]);
        }
    }

    public function suiteId()
    {
        $value = $this->data['suite_id'] ?? auth()->config()['suite_id'];

        if($value){
            $this->query->andWhere(['suite_id' => $value]);
        }
    }

    // type
    public function type($value)
    {
        if($value){
            $this->query->andWhere(['type' => $value]);
        }
    }

    public function isDefault($value)
    {
        if($value){
            $this->query->andWhere(['is_default' => $value]);
        }
    }
}

