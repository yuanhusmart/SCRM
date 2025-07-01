<?php

namespace common\validate;

use common\errors\Code;
use common\errors\ErrException;
use common\helpers\ArrayHelper;
use Inhere\Validate\Validation;

class CommonValidate extends Validation
{
    # 进行验证前处理,返回false则停止验证,但没有错误信息,可以在逻辑中调用 addError 增加错误信息
    public function beforeValidate(): bool {
        return true;
    }

    /**
     * @throws ErrException
     */
    public function afterValidate(): void {

        if ($this->isFail()) {
            throw new ErrException(Code::PARAMS_ERROR, $this->firstError());
        }

    }

    public static function validator(array $data, $scene = '', $whetherHandleAction = true) {
        $validate = parent::make($data);

        if ($scene) {
            $scene  = $whetherHandleAction ? lcfirst(str_replace('action', '', $scene)) : $scene;
            $scenes = $validate->scenarios();
            if (!isset($scenes[$scene])) {
                return '';
            }
            $validate->atScene($scene);
        }
        return $validate->validate()->firstError();
    }

    /** 自定义验证器的提示消息, 默认消息请看 {@see ErrorMessageTrait::$messages} */
    public function messages(): array {
        return [
            'required'   => '{attr} 是必填项。',
            'requiredIf' => '{attr} 是必填项。',
            'string'     => '{attr} 必须是字符串。',
            'regexp'     => '{attr} 不合法。',
            'email'      => '{attr} 不合法。',
            'int'        => '{attr} 必须是整形数值。',
            'array'      => '{attr} 必须是数组。',
            'number'     => '{attr} 必须是大于0的整数',
            'distinct'   => '{attr} 数组中的值必须是唯一的',
            'customEachRequired' => '{attr} 缺少必填参数',
        ];
    }

    /**
     * 错误提示
     * @return array
     */
    public function translates(): array {
        return [
            'page'       => '页数',
            'size'       => '每页数量',
            'start_time' => '开始时间',
            'end_time'   => '结束时间',
            'mobile'     => '手机号',
            'user_id'    => '用户ID',
            'brand_id'   => '品牌ID',
            'created_at' => '添加时间',
        ];
    }

    /**
     * 自定定义校验数组中某键是否存在且不为空
     * @param $data
     * @param $field
     * @return bool
     * @author 龚德铭
     * @date 2023/10/26 10:42
     */
    public function customEachRequiredValidator($data, $field)
    {

        if (empty($data) || empty($field)) {
            return true;
        }

        if (!is_array($data)) {
            return false;
        }

        $field = is_array($field) ? $field : explode(',', $field);
        $data   = ArrayHelper::isManyArray($data) ? $data : [$data];
        $return = true;
        foreach ($data as $dv) {
            foreach ($field as $fv) {
                if (!isset($dv[$fv]) || empty($dv[$fv])) {
                    $return = false;
                    break;
                }
            }
        }

        return $return;
    }
}