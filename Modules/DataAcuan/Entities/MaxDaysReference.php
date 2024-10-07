<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Enums;
use App\Traits\Uuids;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\LogMaxDaysReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MaxDaysReference extends Model
{
    use LogsActivity;
    use SoftDeletes;
    use HasFactory;
    use Uuids;
    use Enums;

    protected $guarded = [
        "maximum_days_for",
        "maximum_days",
        "description",
        "year",
    ];

    protected $enumMaximumDaysFors = [1,2];


    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\IndirectMaxReportFactory::new ();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(["*"]);
    }

    public function log()
    {
        return $this->hasMany(LogMaxDaysReference::class, "max_days_reference_id", "id");
    }

    public function lastLogIndirect()
    {
        return $this->hasOne(LogMaxDaysReference::class, "max_days_reference_id", "id")
            ->whereHas("maxDays", function ($QQQ) {
                return $QQQ->where("maximum_days_for", "1");
            })
            ->orderBy("created_at", "desc");
    }
   
    public function lastLogAgenda()
    {
        return $this->hasOne(LogMaxDaysReference::class, "max_days_reference_id", "id")
            ->whereHas("maxDays", function ($QQQ) {
                return $QQQ->where("maximum_days_for", "2");
            })
            ->orderBy("created_at", "desc");
    }
}
