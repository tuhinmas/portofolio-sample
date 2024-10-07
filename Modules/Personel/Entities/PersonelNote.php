<?php

namespace Modules\Personel\Entities;

use App\Traits\Uuids;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Modules\Authentication\Entities\User;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PersonelNote extends Model
{
    use HasFactory, Uuids, SoftDeletes, LogsActivity;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\Personel\Database\factories\PersonelNoteFactory::new();
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

    public function user(){
        return $this->hasOne(User::class, "id", "user_id");
    }

    public function personel(){
        return $this->hasOne(Personel::class, "id", "personel_id");
    }
}
