<?php

namespace app\transformers\SuiteRole;

use app\transformers\Helper;
use common\models\concerns\enums\SuiteRole\Type;

class IndexTransformer
{
    public function transform($item)
    {
        $data = $item->toArray();

        Helper::timeFormat($data, ['created_at', 'updated_at']);

        $data['type_presenter'] = Type::enum($data['type']);

        return $data;
    }
}