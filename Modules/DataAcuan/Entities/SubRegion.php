<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\MarketingArea;
use App\Traits\Uuids;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\Address\Entities\City;
use Modules\Analysis\Entities\SubRegionOrderRecapPerMonth;
use Modules\DataAcuan\Entities\MarketingAreaCity;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Entities\Region;
use Modules\Event\Entities\EventArea;
use Modules\Personel\Entities\Personel;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SubRegion extends Model
{
    use CascadeSoftDeletes;
    use MarketingArea;
    use LogsActivity;
    use SoftDeletes;
    use HasFactory;
    use Uuids;

    public $incrementing = false;

    protected $cascadeDeletes = [
        'district',
        'city',
    ];

    protected $dates = ['deleted_at'];

    protected $guarded = [];
    protected $table = 'marketing_area_sub_regions';

    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\SubRegionFactory::new ();
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

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id', 'id')->with('provinceRegion');
    }

    public function city()
    {
        return $this->hasMany(MarketingAreaCity::class, 'sub_region_id', 'id')
            ->orderBy(
                City::select('name')
                    ->whereColumn('marketing_area_cities.city_id', 'indonesia_cities.id')
                    ->take(1),
                'asc'
            )
            ->with(["district", "personel", "personel.position", "city"]);
    }

    public function personel()
    {
        return $this->belongsTo(Personel::class, "personel_id", "id")->with("position");
    }

    public function attachCity()
    {
        return $this->belongsToMany(City::class, "marketing_area_cities", "sub_region_id", "city_id")
            ->withPivot(['created_at', 'updated_at'])
            ->using(new class extends Pivot
        {
                use Uuids;
            });
    }

    public function district()
    {
        return $this->hasMany(MarketingAreaDistrict::class, "sub_region_id", "id");
    }

    public function districts()
    {
        return $this->hasMany(MarketingAreaDistrict::class, "sub_region_id", "id");
    }

    public function scopeAsSuperVisor($QQQ, $personel_id)
    {
        $personel_detail = Personel::with("position")->where("id", $personel_id)->first();
        $region_id = DB::table('marketing_area_regions')->whereNull("deleted_at")->where("personel_id", $personel_id)->get()->pluck("id");
        $sub_region_id = DB::table('marketing_area_sub_regions')
            ->whereNull("deleted_at")
            ->where("personel_id", $personel_id)
            ->orWhereIn("region_id", $region_id)
            ->get()
            ->pluck("id");

        $sub_region = SubRegion::query()
            ->whereIn("region_id", $region_id)
            ->orWhereIn("id", $sub_region_id)
            ->get()
            ->pluck("id")
            ->toArray();

        $position_list = [
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
        ];
        if (in_array($personel_detail->position->name, $position_list)) {
            return $QQQ;
        } else {
            return $QQQ->whereIn("id", $sub_region);
        }
    }

    public function eventArea()
    {
        return $this->hasMany(EventArea::class, "marketing_area_sub_region_id", "id");
    }

    public function scopeByEvent($query, $event_id)
    {
        return $query->whereHas("eventArea", function ($QQQ) use ($event_id) {
            return $QQQ->where("event_id", $event_id);
        });
    }

    /* personel branch */
    public function scopePersonelBranch($query, $personel_id = null)
    {
        $branch_area = DB::table('personel_branches')
            ->whereNull("deleted_at")
            ->where("personel_id", $personel_id ? $personel_id : auth()->user()->personel_id)
            ->pluck("region_id");

        $marketing_on_branch = $this->marketingListOnPersonelBranch($branch_area);
        return $query->whereIn("personel_id", $marketing_on_branch);
    }

    public function subRegionRecap()
    {
        return $this->hasMany(SubRegionOrderRecapPerMonth::class, "sub_region_id", "id");
    }
}
