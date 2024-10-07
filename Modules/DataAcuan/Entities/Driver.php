<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use App\Traits\SuperVisorCheckV2;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\DistributionChannel\Entities\DispatchOrder;

class Driver extends Model
{
    use HasFactory;

    use SoftDeletes;
    use Uuids;
    use SuperVisorCheckV2;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\DriverFactory::new();
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

    public function personel()
    {
        return $this->belongsTo(Personel::class, "id_driver", "id")->with("position");
    }

    public function dispatchOrder(){
        return $this->hasMany(DispatchOrder::class, "id_armada", "id");
    }
}
