<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use App\Traits\SuperVisorCheckV2;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Product;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\ProductGroup\Entities\ProductGroup;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\DataAcuan\Entities\ProductMandatoryPivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductMandatory extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Uuids;
    use SuperVisorCheckV2;
    use CascadeSoftDeletes;

    protected $guarded = [];
    
    protected $cascadeDeletes = [
        'productMandatoryMember',
    ];

    protected $dates = ['deleted_at'];

    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\ProductMandatoryFactory::new();
    }

    public function product(){
        return $this->belongsTo(Product::class,'product_id', 'id');
    }

    public function product_mandatory()
    {
        return $this->hasOne(SalesOrderDetail::class, 'product_id', 'product_id');
    }

    public function productMandatoryMember(){
        return $this->belongsToMany(Product::class, ProductMandatoryPivot::class, "product_mandatory_id", "product_id")->withTimeStamps();
    }

    public function productMember(){
        return $this->hasMany(ProductMandatoryPivot::class, "product_mandatory_id", "id");
    }

    public function productGroup(){
        return $this->belongsTo(ProductGroup::class, "product_group_id", "id");
    }
}
