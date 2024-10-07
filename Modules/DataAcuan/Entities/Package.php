<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Product;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\SalesOrder\Entities\v2\SalesOrderDetail;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Package extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;

    public $incrementing = false;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\PackageFactory::new();
    }
    
    public function product(){
        return $this->belongsTo(Product::class,'product_id', 'id');
    }
    
    public function salesOrderDetail(){
        return $this->belongsTo(SalesOrderDetail::class,'id', 'package_id');
    }
}
