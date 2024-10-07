<?php

namespace Modules\DataAcuan\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Product;
use Modules\ProductGroup\Entities\ProductGroup;
use Modules\DataAcuan\Entities\ProductMandatory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\DataAcuan\Database\factories\ProductMandatoryPivotFactory;

class ProductMandatoryPivot extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return ProductMandatoryPivotFactory::new();
    }

    public function product(){
        return $this->belongsTo(Product::class, "product_id", "id")->withTrashed();
    }

    public function productMandatory(){
        return $this->belongsTo(ProductMandatory::class, "product_mandatory_id", "id");
    }
}
