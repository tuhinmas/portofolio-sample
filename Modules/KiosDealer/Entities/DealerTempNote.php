<?php

namespace Modules\KiosDealer\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Personel\Entities\Personel;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DealerTempNote extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Uuids;
    use LogsActivity;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\KiosDealer\Database\factories\DealerTempNoteFactory::new();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["*"]);
        // Chain fluent methods for configuration options
    }

    public function dealerTemp(){
        return $this->belongsTo(DealerTemp::class, "dealer_temp_id", "id");
    }
 
    public function personel(){
        return $this->belongsTo(Personel::class, "personel_id", "id");
    }

}
