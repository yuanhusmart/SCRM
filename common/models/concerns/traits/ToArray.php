<?php

namespace common\models\concerns\traits;

use Illuminate\Support\Str;
use yii\base\Model;

trait ToArray
{
    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        $data = parent::toArray($fields, $expand, $recursive);

        return array_merge($data, $this->extractRelatedRecords($this));
    }


    private function extractModel($model)
    {
        if (!$model) {
            return null;
        }

        if ($model instanceof Model) {
            $data = $model->toArray();
        } else {
            $data = $model;
        }

        return array_merge($data, $this->extractRelatedRecords($model));
    }

    private function extractRelatedRecords($model)
    {
        $data = [];

        if ($model instanceof Model) {
            foreach ($model->getRelatedRecords() as $key => $value) {
                $key = Str::snake($key);

                if (is_array($value)) {
                    $data[$key] = array_map(function ($model) {
                        return $this->extractModel($model);
                    }, $value);
                } else {
                    $data[$key] = $this->extractModel($value);
                }
            }
        }

        return $data;
    }

}