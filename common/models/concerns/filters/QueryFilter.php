<?php

namespace common\models\concerns\filters;

use common\db\ActiveQuery;
use Illuminate\Support\Str;

class QueryFilter implements Filter
{
    /**
     * @var array
     */
    protected $data;
    /**
     * @var ActiveQuery
     */
    protected $query;

    /**
     * @var array 默认需要执行的查询方法
     */
    public $defaultFilters = [];


    public function __construct(array $data)
    {
        $this->data = $data;
    }


    public function apply(ActiveQuery $query): ActiveQuery
    {
        $this->query = $query;

        foreach ($this->filters() as $method => $value) {
            if (!method_exists($this, $method)) {
                $method = $this->snakeToCamel($method);
            }

            if (method_exists($this, $method)) {
                call_user_func_array([$this, $method], [$value]);
            }
        }

        return $this->query;
    }

    public function filters(): array
    {
        $filters = $this->data;

        if ($this->defaultFilters) {
            $default = [];

            foreach ($this->defaultFilters as $method) {
                $default[$method] = 1;
            }

            $filters = array_merge($default, $filters);
        }

        return $filters;
    }

    private function snakeToCamel($snakeCaseString)
    {
        $words           = explode('_', $snakeCaseString);
        $camelCaseString = '';
        foreach ($words as $index => $word) {
            if ($index === 0) {
                $camelCaseString .= $word;
            } else {
                $camelCaseString .= ucfirst($word);
            }
        }
        return $camelCaseString;
    }

    public function with($value)
    {
        if ($value) {
            $value = array_map([Str::class, 'camel'], $value);

            $this->query->with($value);
        }
    }

    public function sort($value)
    {
        $value = $this->data['sort'] ?? ['id' => SORT_DESC];

        if ($value) {
            $this->query->orderBy($value);
        }
    }

    public function column($value)
    {
        if ($value) {
            $this->query->select($value);
        }
    }
}