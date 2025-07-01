<?php

namespace app\validators\SuiteSpeechCategory;

use common\helpers\validator\Validator;
use common\models\SuiteSpeechCategory;

class CreateValidator extends Validator
{
    public function rules()
    {
        return [
            ['name', 'required'],
            ['name', 'validateNameUnique'],
        ];
    }


    public function validateNameUnique($attribute, $params)
    {
        $name     = $this->$attribute;
        $suiteId  = input('suite_id', auth()->suiteId());
        $corpId   = input('corp_id', auth()->config()['corp_id']);
        $parentId = input('parent_id', 0);

        $model = SuiteSpeechCategory::find()
            ->andWhere([
                'name'      => $name,
                'suite_id'  => $suiteId,
                'corp_id'   => $corpId,
                'parent_id' => $parentId,
            ])->one();

        if ($model) {
            $this->addError($attribute, '分类名已存在');
        }
    }
}