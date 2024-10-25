<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Pricecurrent\LaravelEloquentFilters\AbstractEloquentFilter;

class NameFilter extends AbstractEloquentFilter
{
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function apply($query):Builder
    {
        return $query->where('name', 'like', "%".$this->name."%");
    }
}
