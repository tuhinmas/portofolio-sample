<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\SuperVisorCheckV2;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Address\Entities\City;
use Modules\Address\Entities\District;
use Modules\Address\Entities\Province;
use Modules\Organisation\Entities\Organisation;
use Modules\Personel\Entities\Personel;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


class Warehouse extends Model
{
    use HasFactory;

    use SoftDeletes;
    use Uuids;
    use SuperVisorCheckV2;

    protected $guarded = [];


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

    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\WarehouseFactory::new ();
    }

    public function province()
    {
        return $this->belongsTo(Province::class, "province_id", "id");
    }

    public function city()
    {
        return $this->belongsTo(City::class, "city_id", "id");
    }

    public function district()
    {
        return $this->belongsTo(District::class, "district_id", "id");
    }

    public function personel()
    {
        return $this->belongsTo(Personel::class, "personel_id", "id")->with("position");
    }

    public function organisation()
    {
        return $this->belongsTo(Organisation::class, "id_organisation", "id");
    }

    public function attachPorter()
    {
        return $this->belongsToMany(Personel::class, Porter::class, "warehouse_id", "personel_id")
            ->withTimeStamps()
            ->using(new class extends Pivot
            {
                use Uuids;
                protected $casts = ["id" => "string"];
            });
    }

    public function porter()
    {
        return $this->hasMany(Porter::class, "warehouse_id", "id");
    }

    public function logPorter()
    {
        return $this->hasMany(LogPorter::class, "warehouse_id", "id");
    }

    public function scopeCodeNamePorter($query, $word)
    {
        return $query->where("code","like","%".$word."%")
        ->orWhere("name","like", "%".$word."%")
        ->orWhereHas("porter",function($query) use ($word){
            return $query->whereHas("personel",function($query) use ($word){
                return $query->where("name","like","%".$word."%");
            });
        });
    }

    public function scopeHasPorter($query)
    {
        return $query->whereHas("porter");
    }
}
