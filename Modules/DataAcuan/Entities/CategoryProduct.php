<?php

namespace Modules\DataAcuan\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategoryProduct extends Model
{
    use HasFactory;

    protected $table = "product_categories";
    protected $guraded = [];
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\CategoryProductFactory::new();
    }
}
