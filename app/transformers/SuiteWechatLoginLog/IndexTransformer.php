<?php

namespace app\transformers\SuiteWechatLoginLog;

use app\transformers\Helper;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use common\models\concerns\enums\SuiteWechatLoginLog\Status;

class IndexTransformer
{
    public function transform($item)
    {
        $data = $item->toArray();

        Helper:: timeFormat($data, ['created_at', 'updated_at']);

        $data['status_presenter'] = Status::enum($data['status']);

        if(Arr::has($data, 'work_wx.wx_number')){
            $data['work_wx']['wx_number'] = replaceStr($data['work_wx']['wx_number']);
        }

        if(Arr::has($data, 'account.mobile')){
            $data['account']['mobile'] = replaceStr($data['account']['mobile']);
        }

        return $data;
    }
}