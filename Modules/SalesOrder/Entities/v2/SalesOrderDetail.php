<?php

namespace Modules\SalesOrder\Entities\v2;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\Package;
use Modules\DataAcuan\Entities\Product;
use Modules\SalesOrder\Entities\SalesOrder;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SalesOrderDetail extends Model
{
    use LogsActivity;
    use SoftDeletes;
    use HasFactory;
    use Uuids;

    public $incrementing = false;
    protected $guarded = [];
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

    public function salesOrder()
    {
        return $this->hasOne(SalesOrder::class, 'id', 'sales_order_id');
    }

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id')->with('package', 'category')->withTrashed();
    }

    public function package()
    {
        return $this->hasOne(Package::class, "id", "package_id")->withTrashed();
    }
}
