<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Product;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class feeReguler extends Model
{
    use HasFactory, Uuids, SoftDeletes;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\FeeRegulerFactory::new();
    }

    public function product(){
        return $this->belongsTo(Product::class, "product_id", "id");
    }
}
