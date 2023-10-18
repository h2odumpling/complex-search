<?php

namespace H2o\ComplexSearch\Paginator;

class Paginator extends \Illuminate\Pagination\LengthAwarePaginator
{
    public function toArray()
    {
        return [
            'data' => $this->items->toArray(),
            'total' => $this->total(),
            'size' => $this->perPage(),
            'current_page' => $this->currentPage(),
        ];
    }
}
