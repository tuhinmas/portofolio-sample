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

class PersonnelStructureHistory extends Model
{
    use HasFactory, Uuids, SoftDeletes, LogsActivity;

    protected $guarded = [];
    
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["*"]);
    }

    public function personel()
    {
        return $this->hasOne(Personel::class, "id", "personel_id");
    }

    public function rmc()
    {
        return $this->hasOne(Personel::class, "id", "rmc_id");
    }

    public function asstMdm()
    {
        return $this->hasOne(Personel::class, "id", "asst_mdm_id");
    }

    public function mdm()
    {
        return $this->hasOne(Personel::class, "id", "mdm_id");
    }

    public function mm()
    {
        return $this->hasOne(Personel::class, "id", "mm_id");
    }
}
