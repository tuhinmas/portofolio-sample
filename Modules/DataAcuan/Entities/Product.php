<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\DistributorStock;
use App\Traits\Enums;
use App\Traits\Uuids;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\Fee;
use Modules\DataAcuan\Entities\Package;
use Modules\DataAcuan\Entities\PointProduct;
use Modules\DataAcuan\Entities\Price;
use Modules\DataAcuan\Entities\ProductCategory;
use Modules\Distributor\Entities\DistributorProduct;
use Modules\Event\Entities\EventProductBundle;
use Modules\Event\Entities\EventSalesEstimation;
use Modules\Invoice\Entities\AdjustmentStock;
use Modules\ProductGroup\Entities\ProductGroup;
use Modules\ProductGroup\Entities\ProductGroupMember;
use Modules\SalesOrderV2\Entities\SalesOrderDetailV2;
use Modules\SalesOrderV2\Entities\SalesOrderV2;
use Modules\SalesOrder\Entities\SalesOrderDetail;

class Product extends Model
{
    use Uuids;
    use Enums;
    use HasFactory;
    use SoftDeletes;
    use DistributorStock;
    use CascadeSoftDeletes;

    public $incrementing = false;

    protected $cascadeDeletes = [
        'pointProduct',
        'price',
    ];

    protected $dates = ['deleted_at'];
    protected $guarded = [];
    protected $appends = [
        "in_the_group",
    ];

    protected $enumMetricUnits = [
        "Kg",
        "Liter",
    ];

    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\ProductFactory::new ();
    }

    public function package()
    {
        return $this->hasOne(Package::class, 'product_id', 'id')->where('isActive', '1');
    }

    public function allPackage()
    {
        return $this->hasMany(Package::class, 'product_id', 'id');
    }

    public function price()
    {
        return $this->hasMany(Price::class, 'product_id', 'id')->orderBy('minimum_order');
    }

    public function priceHasOneV2()
    {
        return $this->hasOne(Price::class, 'product_id', 'id')
            ->whereHas("agencyLevel", function ($QQQ) {
                return $QQQ->where("name", "D1");
            })
            ->orderBy('price');
    }

    public function priceHasOne()
    {
        return $this->hasOne(PriceHistory::class, 'product_id', 'id')
            ->whereDate('valid_from', '<=', date('Y-m-d'))
            ->whereHas("agencyLevel", function ($QQQ) {
                return $QQQ->where("name", "D1");
            })
            ->orderByDesc('valid_from');
    }

    public function priceCheapToExpensive()
    {
        return $this->hasMany(Price::class, 'product_id', 'id')->with("agency_level")->orderBy('minimum_order', 'desc');
    }

    public function lowerPrice()
    {
        return $this->hasOne(Price::class, 'product_id', 'id')->orderBy('price');
    }

    public function priceByDealer()
    {
        return $this->hasOne(Price::class, 'product_id', 'id');
    }

    public function priceD1()
    {
        return $this->hasOne(Price::class, 'product_id', 'id')
        // ->whereDate('valid_from', '<=', date('Y-m-d'))
            ->whereHas("agencyLevel", function ($QQQ) {
                return $QQQ->where("name", "D1");
            })
            ->orderBy('price');
    }

    public function category()
    {
        return $this->hasOne(ProductCategory::class, "id", "category_id");
    }

    public function categoryProduct()
    {
        return $this->hasOne(ProductCategory::class, "id", "category_id");
    }

    public function scopeProductDirect($QQQ, $delaer_id)
    {
        $dealer = DB::table('dealers')->whereNull("deleted_at")->where("id", $delaer_id)->first();
        if ($dealer->is_distributor) {
            # code...
        }
        $dealer_address = DB::table('address_with_details')
            ->whereNull("deleted_at")
            ->where("parent_id", $delaer_id)
            ->where("type", "dealer")
            ->first();

        $distributor_area = DistributorArea::query()
            ->with("contract")
            ->where("distrit_id", $dealer_address)
            ->get();

        return $QQQ->where;
    }
    public function productByDealer()
    {
        return $this->hasManyThrough(
            SalesOrderV2::class,
            SalesOrderDetailV2::class,
            'product_id', // Foreign key on the salesorderdetail table...
            'id', // Foreign key on the salesorder table...
            'id', // Local key on the product table...
            'sales_order_id' // Local key on the salesorderdetail table...
        );
    }

    public function pointProduct()
    {
        return $this->hasMany(PointProduct::class, "product_id", "id");
    }

    public function feeProduct()
    {
        return $this->hasMany(Fee::class, "product_id", "id");
    }

    public function salesOrderDetail()
    {
        return $this->hasMany(SalesOrderDetail::class, "product_id", "id");
    }

    public function productMandatory()
    {
        return $this->hasOne(ProductMandatory::class, "product_id", "id");
    }

    public function salesOrderDetailDelete()
    {
        return $this->hasMany(SalesOrderDetail::class, "product_id", "id")
            ->whereHas("sales_order", function ($QQQ) {
                return $QQQ->where("status", "!=", "confirmed");
            });
    }

    /**
     * distribution product
     *
     * @return void
     */
    public function distributorProduct()
    {
        return $this->hasOne(DistributorProduct::class, "product_id", "id");
    }

    public function getInTheGroupAttribute()
    {
        $in_the_group = $this->productGroup()->first();
        if ($in_the_group) {
            return true;
        }
        return false;
    }

    public function productGroup()
    {
        return $this->hasOneThrough(
            ProductGroup::class,
            ProductGroupMember::class,
            'product_id', // Foreign key on the ProductGroupMember table...
            'id', // Foreign key on the ProductGroup table...
            'id', // Local key on the product table...
            'product_group_id' // Local key on the ProductGroupMember table...
        );
    }

    public function productAsMember()
    {
        return $this->belongsTo(ProductGroupMember::class, "id", "product_id");
    }

    public function adjustmentStock()
    {
        return $this->hasMany(AdjustmentStock::class, "product_id", "id");
    }

    public function lastAdjustmentStock()
    {
        return $this->hasOne(AdjustmentStock::class, "product_id", "id")->orderBy("opname_date", "desc");
    }

    public function eventSalesEstimation()
    {
        return $this->hasOne(EventSalesEstimation::class, "product_id", "id");
    }

    public function eventProductBundle()
    {
        return $this->hasOne(EventProductBundle::class, "product_id", "id");
    }

    public function scopeUnsetFirstStockDistributorProduct($query, $distributor_id)
    {
        $active_contract = $this->distributorActiveContract($distributor_id);
        return $query
            ->when($active_contract, function ($QQQ) use ($active_contract, $distributor_id) {
                $adjusment_stock = DB::table('adjustment_stock')->whereNull("deleted_at")
                    ->where("dealer_id", $distributor_id)
                    ->where("opname_date", ">=", $active_contract->contract_start)
                    ->where("opname_date", "<=", $active_contract->contract_end)
                    ->where("is_first_stock", "1")
                    ->pluck("product_id")
                    ->toArray();

                return $QQQ
                    ->whereNotIn("id", $adjusment_stock);
            });
    }

    public function scopeProductMarketing($query)
    {
        $is_marketing = in_array(auth()->user()->profile?->position?->name, marketing_positions());
        return $query
            ->when($is_marketing, function ($QQQ) {
                return $QQQ->whereHas("category", function ($QQQ) {
                    return $QQQ->whereIn("name", ["a", "b"]);
                });
            });
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        // static::deleted(function ($product) {
        //     $product->pointProduct()->delete();
        //     $product->price()->delete();
        //     $product->salesOrderDetail()->whereHas("sales_order", function ($QQQ) {
        //         return $QQQ->where("status", "!=", "confirmed");
        //     })->delete();
        // });
    }
}
