<?php

namespace common\models\concerns\traits;

trait SoftDeletes
{
    public static function find()
    {
        $query = parent::find();

        $alias = $query->getAlias();

        $query->andWhere(["{$alias}.deleted_at" => 0]);

        return $query;
    }

}