<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Country;
use Modules\Personel\Entities\Personel;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Bank extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;
    use LogsActivity;

    public $incrementing = false;
    protected $guarded = [];

    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\BankFactory::new ();
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

    public function country()
    {
        return $this->hasMany(Country::class, 'id', 'country_id');
    }

    public function personel()
    {
        return $this->belongsToMany(Personel::class, 'bank_personels');
    }

    public function countryOfBank()
    {
        return $this->hasOne(Country::class, 'id', 'country_id');
    }
}
