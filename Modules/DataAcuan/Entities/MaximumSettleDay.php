<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MaximumSettleDay extends Model
{
    use LogsActivity;
    use SoftDeletes;
    use HasFactory;
    use Uuids;

    protected $guarded = [];
    

    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\MaximumSettleDayFactory::new();
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

    public function personel(){
        return $this->hasOne(Personel::class, "id", "personel_id");
    }
}
