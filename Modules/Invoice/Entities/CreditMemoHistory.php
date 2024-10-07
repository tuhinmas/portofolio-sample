<?php

namespace Modules\Invoice\Entities;

use App\Traits\Enums;
use App\Traits\Uuids;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CreditMemoHistory extends Model
{
    use Uuids;
    use Enums;
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected $enumStatuses = [
        "accepted",
        "canceled",
    ];
    protected $guarded = [
        "id",
        "created_at",
        "updated_at",
        "deleted_at",
    ];

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(["*"]);
    }
    
    protected static function newFactory()
    {
        return \Modules\Invoice\Database\factories\CreditMemoHistoryFactory::new();
    }
}
