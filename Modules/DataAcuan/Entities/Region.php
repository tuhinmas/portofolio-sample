<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use App\Traits\MarketingArea;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Modules\Address\Entities\Province;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Modules\Contest\Entities\ContestArea;
use Modules\DataAcuan\Entities\SubRegion;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\ProvinceRegion;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Analysis\Entities\RegionOrderRecapPerMonth;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Region extends Model
{
    use MarketingArea;
    use LogsActivity;
    use SoftDeletes;
    use HasFactory;
    use Uuids;

    public $incrementing = false;
    protected $guarded = [];
    protected $table = 'marketing_area_regions';

    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\RegionFactory::new ();
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

    // public function province(){
    //     return $this->hasMany(Province::class, 'id', 'parent_id');
    // }

    public function subRegion()
    {
        return $this->hasMany(SubRegion::class, 'region_id', 'id')->orderBy('name', 'asc')->with("city", "district");
    }

    public function subRegions()
    {
        return $this->hasMany(SubRegion::class, 'region_id', 'id');
    }

    public function subRegionOnly()
    {
        return $this->hasMany(SubRegion::class, 'region_id', 'id')->select("id", "name", "region_id")->orderBy('name', 'asc');
    }

    public function provinceRegion()
    {
        return $this->belongsToMany(Province::class, ProvinceRegion::class, "region_id", "province_id")->withTimestamps();
        // return $this->belongsToMany(Province::class,"province_regions","region_id", "province_id")->withTimestamps();
    }

    public function personel()
    {
        return $this->hasOne(Personel::class, "id", "personel_id")->with("position");
    }

    public function scopeAsSuperVisor($QQQ, $personel_id)
    {
        $personel_detail = Personel::with("position")->where("id", $personel_id)->first();
        $region_id = DB::table('marketing_area_regions')->whereNull("deleted_at")->where("personel_id", $personel_id)->get()->pluck("id");
        $sub_region_id = DB::table('marketing_area_sub_regions')->whereNull("deleted_at")->where("personel_id", $personel_id)->get()->pluck("region_id");

        $sub_region = SubRegion::query()
            ->whereIn("region_id", $region_id)
            ->orWhereIn("region_id", $sub_region_id)
            ->get()
            ->pluck("region_id")
            ->toArray();

        if (auth()->user()->hasAnyRole(
            'administrator',
            'super-admin',
            'Marketing Support',
            'Marketing Manager (MM)',
            'Sales Counter (SC)',
            'Operational Manager',
            'Support Bagian Distributor',
            'Support Bagian Kegiatan',
            'Support Distributor',
            'Support Kegiatan',
            'Support Supervisor',
            'Distribution Channel (DC)',
            'User Jember'
        )) {
            return $QQQ;
        } else {
            return $QQQ->whereIn("id", $sub_region);
        }
    }

    /* personel branch */
    public function scopePersonelBranch($query)
    {
        $branch_area = DB::table('personel_branches')->whereNull("deleted_at")->where("personel_id", auth()->user()->personel_id)->pluck("region_id");
        $marketing_on_branch = $this->marketingListOnPersonelBranch($branch_area);
        return $query->whereIn("personel_id", $marketing_on_branch);
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::deleted(function ($dealer) {
            $dealer->provinceRegion()->delete();
        });
    }

    public function contestArea()
    {
        return $this->hasMany(ContestArea::class, "id", "region_id");
    }

    public function regionRecap()
    {
        return $this->hasMany(RegionOrderRecapPerMonth::class, "region_id", "id");
    }

    /**
     * Get all of the comments for the Region
     *
     */
    public function districts(): HasManyThrough
    {
        return $this->hasManyThrough(
            MarketingAreaDistrict::class,
            SubRegion::class, 
            "region_id",
            "sub_region_id",
            "id",
            "id"
        );
    }
}
