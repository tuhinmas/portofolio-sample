<?php

namespace Modules\SalesOrder\Entities;

use App\Traits\Uuids;
use Spatie\Activitylog\LogOptions;
use Modules\DataAcuan\Entities\Fee;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Package;
use Modules\DataAcuan\Entities\Product;
use Spatie\Activitylog\Contracts\Activity;
use Modules\SalesOrder\Entities\SalesOrder;
use Spatie\Activitylog\Traits\LogsActivity;
use Modules\DataAcuan\Entities\PointProduct;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Invoice\Entities\AdjustmentStock;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Modules\Contest\Entities\ContestPointOrigin;
use Modules\SalesOrder\Entities\SalesOrderOrigin;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Modules\SalesOrder\Traits\SalesOrderDetailTrait;
use Modules\SalesOrder\Traits\ScopeSalesOrderDetail;
use Modules\DataAcuan\Entities\ProductMandatoryPivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\ReceivingGood\Entities\ReceivingGoodDetail;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\DistributionChannel\Entities\DispatchOrderDetail;

class SalesOrderDetail extends Model
{
    use ScopeSalesOrderDetail;
    use SalesOrderDetailTrait;
    use CascadeSoftDeletes;
    use LogsActivity;
    use SoftDeletes;
    use HasFactory;
    use Uuids;

    public $incrementing = false;
    protected $guarded = [];
    protected $cascadeDeletes = [
        "allSalesOrderOrigin",
        "allSalesOrderOrigin",
        "contestPointOrigin",
        "feeSharingOrigin",
        "feeTargetOrigin",
    ];
    protected $dates = ['deleted_at'];
    protected $appends = [
        "sub_total",
    ];

    protected static function newFactory()
    {
        return \Modules\SalesOrder\Database\factories\SalesOrderDetailFactory::new ();
    }

    /**
     * activity logs set causer
     *
     * @param Activity $activity
     * @param string $eventName
     * @return void
     */
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }

    /**
     * activity logs
     *
     * @param Activity $activity
     * @param string $eventName
     * @return void
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["*"]);
        // Chain fluent methods for configuration options
    }

    public function sales_order()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'id')->with("invoice");
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id', 'id')->with("invoice");
    }

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id')->with('package', 'category')->withTrashed();
    }

    public function product_mandatory()
    {
        return $this->belongsTo(ProductMandatoryPivot::class, 'product_id', 'product_id');
    }

    public function package()
    {
        return $this->hasOne(Package::class, "id", "package_id")->withTrashed();
    }

    public function getSubTotalAttribute()
    {        
        return (float) $this->unit_price * ($this->quantity -$this->getOriginal("returned_quantity"));
    }

    public function receivingGoodDetaill()
    {
        return $this->hasMany(ReceivingGoodDetail::class, "product_id", "product_id");
    }

    public function dispatchOrderDetail()
    {
        return $this->HasMany(DispatchOrderDetail::class, "id_product", "product_id");
    }

    public function contestPointOrigin()
    {
        return $this->hasMany(ContestPointOrigin::class, "sales_order_details_id", "id");
    }

    public function feeProduct()
    {
        return $this->hasMany(Fee::class, "product_id", "product_id")->where("year", now()->format("Y"));
    }

    public function allFeeProduct()
    {
        return $this->hasMany(Fee::class, "product_id", "product_id");
    }

    public function pointProductAllYear()
    {
        return $this->hasMany(PointProduct::class, "product_id", "product_id");
    }

    public function lasTAdjusmentStockDistributor()
    {
        return $this->hasOne(AdjustmentStock::class, "product_id", "product_id");
    }

    public function feeTargetOrigin()
    {
        return $this->hasMany(FeeTargetSharingSoOrigin::class, "sales_order_detail_id", "id");
    }

    public function salesOrderOrigin()
    {
        return $this->hasOne(SalesOrderOrigin::class, "sales_order_detail_id", "id");
    }

    public function allSalesOrderOrigin()
    {
        return $this->hasMany(SalesOrderOrigin::class, "sales_order_detail_id", "id");
    }

    public function feeSharingOrigin()
    {
        return $this->hasMany(FeeSharingSoOrigin::class, "sales_order_detail_id", "id");
    }
}
