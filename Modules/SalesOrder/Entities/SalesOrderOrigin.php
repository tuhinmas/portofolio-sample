<?php

namespace Modules\SalesOrder\Entities;

use App\Traits\Uuids;
use Spatie\Activitylog\LogOptions;
use App\Traits\SalesOrderOriginTrait;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Product;
use Spatie\Activitylog\Contracts\Activity;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\SalesOrder\Entities\SalesOrder;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Entities\LogSalesOrderOrigin;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesOrderOrigin extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;
    use LogsActivity;
    use SalesOrderOriginTrait;

    protected $guarded = [
        "created_at",
        "updated_at",
    ];

    protected static function newFactory()
    {
        return \Modules\SalesOrder\Database\factories\SalesOrderOriginFactory::new ();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["*"]);
    }

    public function direct()
    {
        return $this->belongsTo(SalesOrder::class, "direct_id", "id");
    }

    public function parent()
    {
        return $this->belongsTo(SalesOrder::class, "id", "parent_id");
    }
    
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, "sales_order_id", "id");
    }

    public function salesOrderDetail()
    {
        return $this->belongsTo(SalesOrderDetail::class, "sales_order_detail_id", "id");
    }

    public function product()
    {
        return $this->belongsTo(Product::class, "product_id", "id");
    }

    public function dealerAsPurchaser()
    {
        return $this->belongsTo(DealerV2::class, "store_id", "id");
    }

    public function subDealerAsPurchaser()
    {
        return $this->belongsTo(DealerV2::class, "store_id", "id");
    }

    public function dealerAsDistributor()
    {
        return $this->belongsTo(DealerV2::class, "distributor_id", "id");
    }

    public function logViaDetail(){
        return $this->hasOne(LogSalesOrderOrigin::class, "sales_order_detail_id", "sales_order_detail_id");
    }
}
