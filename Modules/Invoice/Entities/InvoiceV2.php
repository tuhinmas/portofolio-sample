<?php

namespace Modules\Invoice\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Modules\PersonelBranch\Entities\PersonelBranch;
use Modules\SalesOrder\Entities\SalesOrder;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InvoiceV2 extends Model
{
    use HasFactory, Uuids, SoftDeletes, LogsActivity;

    protected $table = "invoices";

    protected $guarded = [];

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(["*"]);
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, "sales_order_id", "id");
    }
}
