<?php

namespace Modules\SalesOrder\Entities;

use App\Traits\Uuids;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Modules\DataAcuan\Entities\Position;
use Modules\DataAcuan\Entities\StatusFee;
use Spatie\Activitylog\Contracts\Activity;
use Modules\DataAcuan\Entities\FeePosition;
use Modules\SalesOrder\Entities\SalesOrder;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Modules\SalesOrder\Entities\SalesOrderDetail;
use Modules\SalesOrder\Entities\SalesOrderOrigin;
use Modules\Personel\Entities\LogMarketingFeeCounter;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FeeSharingSoOrigin extends Model
{
    use Uuids;
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;
    use CascadeSoftDeletes;

    protected $guarded = [
        "created_at",
        "updated_at",
    ];

    protected $cascadeDeletes = ['logMarketingFeeCounter'];
    protected $dates = ['deleted_at'];

    protected static function newFactory()
    {
        return \Modules\SalesOrder\Database\factories\FeeSharingSoOriginFactory::new ();
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

    public function salesOrderDetail()
    {
        return $this->hasOne(SalesOrderDetail::class, "id", "sales_order_detail_id");
    }

    public function personel()
    {
        return $this->belongsTo(Personel::class, "personel_id", "id");
    }

    public function position()
    {
        return $this->belongsTo(Position::class, "position_id", "id");
    }

    public function salesOrder()
    {
        return $this->hasOne(SalesOrder::class, "id", "sales_order_id");
    }

    public function statusFee()
    {
        return $this->HasOne(StatusFee::class, "id", "status_fee");
    }

    public function logMarketingFeeCounter()
    {
        return $this->hasOne(LogMarketingFeeCounter::class, "sales_order_id", "sales_order_id");
    }

    public function feePosition()
    {
        return $this->hasOne(FeePosition::class, "position_id", "position_id");
    }

    /*
    |--------------------------------
    | SCOPE LIST
    |---------------------
    |*/
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
