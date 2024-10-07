<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\ChildrenList;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\Address\Entities\City;
use Modules\Address\Entities\District;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\Personel\Entities\Personel;

class MarketingAreaCity extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;
    use ChildrenList;

    public $incrementing = false;
    protected $guarded = [];
    protected $table = 'marketing_area_cities';
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\MarketingAreaCityFactory::new ();
    }

    public function subRegion()
    {
        return $this->belongsTo(SubRegion::class, 'sub_region_id', 'id')->with("region");
    }

    public function district()
    {
        return $this->hasMany(MarketingAreaDistrict::class, 'city_id', 'city_id')->with("district", "personel");
    }

    public function personel()
    {
        return $this->belongsTo(Personel::class, "personel_id", "id");
    }
    public function city()
    {
        return $this->hasOne(City::class, "id", "city_id")
            ->with("province");
    }

    /**
     * personel branch scope
     *
     * @param [type] $query
     * @return void
     */
    public function scopePersonelBranch($query)
    {
        $branch_area = DB::table('personel_branches')->whereNull("deleted_at")->where("personel_id", auth()->user()->personel_id)->pluck("region_id");

        /* get sub region by region_id */
        $sub_region_id = DB::table('marketing_area_sub_regions')
            ->whereNull("deleted_at")
            ->whereIn("region_id", $branch_area)
            ->pluck("id")
            ->toArray();

        return $query->whereIn("sub_region_id", $sub_region_id);
    }

    /**
     * supervisor
     *
     * @param [type] $query
     * @param [type] $personel_id
     * @return void
     */
    public function scopeAsSuperVisor($query, $personel_id)
    {
        $personel_subordinate = $this->getChildren($personel_id);

        /* get sub region */
        $sub_region_id = DB::table('marketing_area_sub_regions')
            ->whereNull("deleted_at")
            ->whereIn("personel_id", $personel_subordinate)
            ->pluck("id")
            ->toArray();

        if (auth()->user()->hasAnyRole(
            'administrator',
            'super-admin',
            'Marketing Support',
            'Marketing Manager (MM)',
            'Sales Counter (SC)',
            'Operational Manager',
            'Support Bagian Distributor',
            'Support Distributor',
            'Support Bagian Kegiatan',
            'Support Kegiatan',
            'Support Supervisor',
            'Distribution Channel (DC)',
        )) {
            return $query;
        } else {
            return $query->whereIn("sub_region_id", $sub_region_id);
        }
    }
}
