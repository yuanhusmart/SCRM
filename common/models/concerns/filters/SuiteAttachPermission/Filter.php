<?php

namespace common\models\concerns\filters\SuiteAttachPermission;

use Carbon\Carbon;
use common\models\Account;
use common\models\concerns\enums\SuiteAttachPermission\IsValid;
use common\models\concerns\enums\SuiteAttachPermission\TimeType;
use common\models\concerns\filters\QueryFilter;

class Filter extends QueryFilter
{
    public $defaultFilters = [
        'corpId',
        'suiteId'
    ];

    // suite_id
    public function suiteId()
    {
        $value = $this->data['suite_id'] ?? (auth()->config()['suite_id'] ?? '');

        if ($value) {
            $this->query->andWhere([
                'suite_id' => $value
            ]);
        }
    }

    public function corpId()
    {
        $value  = $this->data['corp_id'] ?? (auth()->config()['corp_id'] ?? '');

        if ($value) {
            $this->query->andWhere([
                'corp_id' => $value
            ]);
        }
    }


    public function accountKeyword($value)
    {
        if (!$value) {
            return;
        }
        $this->query->andWhere([
            'exists',
            Account::find()
                ->andWhere('suite_corp_accounts.id=suite_attach_permission.account_id')
                ->accountKeyword($value)
        ]);
    }

    public function isValid($value)
    {
        if (!$value) {
            return;
        }

        $time = time();

        if ($value == IsValid::VALID) {
            $this->query->andWhere([
                'OR',
                ['time_type' => TimeType::LONG_TERM],
                [
                    'AND',
                    ['<=', 'start_at', $time],
                    ['>=', 'end_at', $time]
                ]
            ]);
        }

        if ($value == IsValid::INVALID) {
            $this->query->andWhere([
                'AND',
                ['time_type' => TimeType::TEMPORARY],
                ['<', 'end_at', $time],
            ]);
        }

        if ($value == IsValid::NOT_EFFECTIVE) {
            $this->query->andWhere([
                'AND',
                ['time_type' => TimeType::TEMPORARY],
                ['>', 'start_at', $time],
            ]);
        }
    }

    public function updatedAtStart($value)
    {
        if ($value) {
            $this->query->andWhere([
                '>=',
                'updated_at',
                Carbon::parse($value)->getTimestamp()
            ]);
        }
    }

    public function updatedAtEnd($value)
    {
        if ($value) {
            $this->query->andWhere([
                '<=',
                'updated_at',
                Carbon::parse($value)->endOfDay()->getTimestamp()
            ]);
        }
    }
}