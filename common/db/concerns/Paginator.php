<?php

namespace common\db\concerns;

use ArrayAccess;

class Paginator implements ArrayAccess
{
    /**
     * @var array 分页数据
     */
    public $items;

    /**
     * @var int 总数
     */
    public $total;

    /**
     * @var int 每页条数
     */
    public $perPage;

    /**
     * @var int 当前页码
     */
    public $currentPage;

    public function __construct($items, $total, $perPage, $currentPage)
    {
        $this->items       = $items;
        $this->total       = $total;
        $this->perPage     = $perPage;
        $this->currentPage = $currentPage;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(array $items): Paginator
    {
        $this->items = $items;
        return $this;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): Paginator
    {
        $this->total = $total;
        return $this;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function setPerPage(int $perPage): Paginator
    {
        $this->perPage = $perPage;
        return $this;
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function setCurrentPage(int $currentPage): Paginator
    {
        $this->currentPage = $currentPage;
        return $this;
    }


    public function toArray()
    {
        return [
            'total'        => $this->total,
            'current_page' => $this->currentPage,
            'per_page'     => $this->perPage,
            'items'        => $this->items,
        ];
    }

    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->items[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }
}