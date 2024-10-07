<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use App\Traits\MarketingArea;
use App\Traits\SupervisorCheck;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Modules\Address\Entities\Province;
use Modules\DataAcuan\Entities\Region;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Laravolt\Indonesia\Models\Kabupaten;
use Laravolt\Indonesia\Models\Kecamatan;
use Modules\DataAcuan\Entities\SubRegion;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\PlantingCalendar\Entities\PlantingCalendar;

class MarketingAreaDistrict extends Model
{
    use SupervisorCheck;
    use MarketingArea;
    use LogsActivity;
    use SoftDeletes;
    use HasFactory;
    use Uuids;

    public $incrementing = false;
    protected $guarded = [];

    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\MarketingAreaDistrictFactory::new ();
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

    public function city()
    {
        return $this->belongsTo(Kabupaten::class, 'city_id', 'id');
    }

    public function district()
    {
        return $this->hasOne(Kecamatan::class, 'id', 'district_id');
    }

    public function personel()
    {
        return $this->hasOne(Personel::class, "id", "personel_id")->with("position");
    }

    public function subRegion()
    {
        return $this->belongsTo(SubRegion::class, "sub_region_id", "id")->with("personel", "personel.position", "region", "region.personel", "region.personel.position")->whereHas("city");
    }

    public function subRegionOnly()
    {
        return $this->belongsTo(SubRegion::class, "sub_region_id", "id");
    }

    public function subRegionWithRegion()
    {
        return $this->hasOne(SubRegion::class, "id", "sub_region_id")->with('region');
    }

    public function region()
    {
        return $this->hasOneThrough(
            Region::class,
            SubRegion::class,
            'id', // Foreign key on the cars table...
            'id', // Foreign key on the owners table...
            'sub_region_id', // Local key on the mechanics table...
            'region_id' // Local key on the cars table...
        );}

    public function province()
    {
        return $this->belongsTo(Province::class, "province_id", "id")->with("region");
    }

    public function plantingCalendar()
    {
        return $this->hasOne(PlantingCalendar::class, "area_id", "id");
    }
    public function cityDistrict()
    {
        return $this->hasOne(Kabupaten::class, 'id', 'city_id');
    }
    public function provinceDistrict()
    {
        return $this->hasOne(Province::class, 'id', 'province_id');
    }

    /* scope supervisor district list */
    public function scopeDistrictSupervisorList($query)
    {
        $personel_id = auth()->user()->personel_id;
        $district_id_list = $this->districtSupervisor($personel_id)["districts"];
        return $query->whereIn("district_id", $district_id_list);
    }

    public function scopeDistrictSubordinateList($query)
    {
        $personel_id = auth()->user()->personel_id;
        $district_id_list = $this->districtSubordinateList($personel_id)["districts"];
        return $query->whereIn("district_id", $district_id_list);
    }

    public function scopeDistrictName($QQQ, $name)
    {
        $district_id = DB::table('indonesia_districts')->where("name", "like", "%" . $name . "%")->get()->pluck("id");
        return $QQQ->whereIn("district_id", $district_id);
    }

    /**
     * Undocumented function
     *
     * @param [type] $QQQ
     * @return void
     */
    public function scopeDistrictListSupervisor($QQQ, $personel_id)
    {
        $district_id = $this->districtListMarketing($personel_id);
        return $QQQ
            ->where(function ($QQQ) use ($district_id, $personel_id) {
                return $QQQ
                    ->whereIn("district_id", $district_id)
                    ->orWhere("applicator_id", $personel_id);
            });
    }

    public function scopeSortByDistrictName($QQQ, $direction)
    {
        return $QQQ->whereHas("district", function ($QQQ) use ($direction) {
            return $QQQ->orderBy("name", $direction);
        });
    }

    public function applicator()
    {
        return $this->hasOne(Personel::class, "id", "applicator_id");
    }
}
