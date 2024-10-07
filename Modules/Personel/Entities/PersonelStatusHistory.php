<?php

namespace Modules\Personel\Entities;

use App\Traits\Uuids;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PersonelStatusHistory extends Model
{
    use LogsActivity;
    use SoftDeletes;
    use HasFactory;
    use Uuids;

    protected $guarded = [];
    
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
        return \Modules\Personel\Database\factories\PersonelStatusHistoryFactory::new();
    }
    
    public function personel()
    {
        return $this->hasOne(Personel::class, "id", "personel_id")->with("position");
    }

    public function change()
    {
        return $this->belongsTo(Personel::class, "change_by", "id");
    }  
}
