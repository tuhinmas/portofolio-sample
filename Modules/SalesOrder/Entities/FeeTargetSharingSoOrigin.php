<?php

namespace Modules\SalesOrder\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\Fee;
use Modules\DataAcuan\Entities\Position;
use Modules\DataAcuan\Entities\Product;
use Modules\DataAcuan\Entities\StatusFee;
use Modules\Personel\Entities\Personel;
use Modules\ReceivingGood\Entities\ReceivingGoodIndirectSale;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Entities\SalesOrderOrigin;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FeeTargetSharingSoOrigin extends Model
{
    use Uuids;
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected $guarded = [
        "created_at",
        "updated_at",
    ];

    protected static function newFactory()
    {
        return \Modules\SalesOrder\Database\factories\FeeTargetSharingSoOriginFactory::new ();
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

    public function salesOrderOrigin()
    {
        return $this->belongsTo(SalesOrderOrigin::class, "sales_order_origin_id", "id");
    }

    public function personel()
    {
        return $this->belongsTo(Personel::class, "personel_id", "id");
    }

    public function position()
    {
        return $this->belongsTo(Position::class, "position_id", "id");
    }

    public function salesOrderDetail()
    {
        return $this->hasOne(SalesOrderDetail::class, "id", "sales_order_detail_id");
    }

    public function salesOrder()
    {
        return $this->hasOne(SalesOrder::class, "id", "sales_order_id");
    }

    public function feeProduct()
    {
        return $this->hasMany(Fee::class, "product_id", "product_id")
            ->where("type", "2");
    }

    public function feeProductTargets()
    {
        return $this->hasMany(Fee::class, "product_id", "product_id")
            ->where("type", "2");
    }

    public function activefeeProduct()
    {
        return $this->hasMany(Fee::class, "product_id", "product_id")->where("year", now()->format("Y"));
    }

    public function statusFee()
    {
        return $this->hasOne(StatusFee::class, "id", "status_fee_id");
    }

    public function lastReceivingGoodIndirect()
    {
        return $this->hasOneThrough(
            ReceivingGoodIndirectSale::class,
            SalesOrder::class,
            "id",
            "sales_order_id",
            "sales_order_id",
            "id"
        )
            ->where("sales_orders.type", "2")
            ->orderBy("receiving_good_indirect_sales.date_received", "desc");
    }

    public function product()
    {
        return $this->hasOne(Product::class, "id", "product_id");
    }

    /*
    |-----------------------
    | SCOPES LIST
    |-----------------
    |*/
    public function scopeFeeTargetMarketing($query, $personel_ids, $year, $quarter)
    {
        return $query
            ->whereIn("personel_id", $personel_ids)
            ->where("is_returned", false)
            ->where(function ($QQQ) {
                return $QQQ
                    ->whereDoesntHave("salesOrderOrigin")
                    ->orWhereHas("salesOrderOrigin", function ($QQQ) {
                        return $QQQ->where("is_fee_counted", true);
                    });
            })
            ->whereHas("salesOrder", function ($QQQ) use ($year, $quarter) {
                return $QQQ
                    ->feeMarketing($year, $quarter)
                    ->whereNull("counter_id")
                    ->confirmedOrder();
            })
            ->whereHas("feeProduct")
            ->countedFeeAccordingOrigin();
    }

    public function scopeFeeTargetMarketingActive($query, $personel_ids, $year, $quarter)
    {
        return $query
            ->whereIn("personel_id", $personel_ids)
            ->where("is_returned", false)
            ->where(function ($QQQ) {
                return $QQQ
                    ->whereDoesntHave("salesOrderOrigin")
                    ->orWhereHas("salesOrderOrigin", function ($QQQ) {
                        return $QQQ->where("is_fee_counted", true);
                    });
            })
            ->whereHas("salesOrder", function ($QQQ) use ($year, $quarter) {
                return $QQQ
                    ->feeMarketingActive($year, $quarter)
                    ->whereNull("counter_id")
                    ->confirmedOrder();
            })
            ->countedFeeAccordingOrigin();
    }

    public function scopeCountedFeeAccordingOrigin($query)
    {
        return $query
            ->where(function ($QQQ) {
                return $QQQ
                    ->whereDoesntHave("salesOrderOrigin")
                    ->orWhereHas("salesOrderOrigin", function ($QQQ) {
                        return $QQQ->where("is_fee_counted", true);
                    });
            });
    }
}
