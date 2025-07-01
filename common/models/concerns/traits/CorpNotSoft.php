<?php

namespace common\models\concerns\traits;

trait CorpNotSoft
{
    /**
     * @return \common\db\ActiveQuery
     */
    public static function corp()
    {
        /** @var \common\db\ActiveQuery $query */
        $query = parent::find();
        $query->andWhere([
            self::asField('suite_id') => auth()->suiteId(),
            self::asField('corp_id') => auth()->corpId(),
        ]);
        return $query;
    }
}