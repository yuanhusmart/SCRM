<?php

namespace app\transformers\SuiteCorpAccount;

use app\transformers\Helper;
use common\models\concerns\enums\SuiteCorpAccount\Status;

class IndexTransformer
{
    public function transform($item)
    {
        $data = $item->toArray();

        Helper:: timeFormat($data, ['created_at', 'updated_at', 'deleted_at']);

        $data['status_presenter'] = Status::enum($data['status']);

        return $data;
    }
}
