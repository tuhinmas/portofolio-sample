<?php

namespace Modules\SalesOrder\Entities\v2;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\Package;
use Modules\DataAcuan\Entities\Product;
use Modules\Invoice\Entities\Invoice;
use Modules\KiosDealer\Entities\Dealer;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SalesOrder extends Model
{
    use LogsActivity;
    use SoftDeletes;
    use HasFactory;
    use Uuids;

    protected $table = "sales_orders";

    public $incrementing = false;
    
    protected $guarded = [];

    protected static function newFactory()
    {
        return \Modules\SalesOrder\Database\factories\SalesOrderFactory::new ();
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
        return LogOptions::defaults()->logOnly(["*"]);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, "id", "sales_order_id");
    }

    public function dealer()
    {
        return $this->hasOne(Dealer::class, "id", "store_id")->withoutAppends();
    }
}
