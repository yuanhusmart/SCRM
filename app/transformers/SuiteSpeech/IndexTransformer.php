<?php

namespace app\transformers\SuiteSpeech;

use app\transformers\Helper;

class IndexTransformer
{
    public function transform($item)
    {
        $data = $item->toArray();

        Helper::timeFormat($data, ['created_at', 'updated_at']);

        return $data;
    }
}