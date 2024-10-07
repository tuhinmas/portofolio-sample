<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\TimeSerilization;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Personel\Entities\Personel;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Contracts\Activity;

class GradingBlock extends Model
{
    use HasFactory, SoftDeletes;
    use Uuids, LogsActivity;
    protected $guarded = [];


    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\GradingBlockFactory::new();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["*"]);
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }

    public function grading()
    {
        return $this->belongsTo(Grading::class, "grading_id", "id");
    }

    public function personel()
    {
        return $this->belongsTo(Personel::class, "personel_id", "id");
    }
}
