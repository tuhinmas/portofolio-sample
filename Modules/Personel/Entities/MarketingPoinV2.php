<?php

namespace Modules\Personel\Entities;

use App\Traits\ChildrenList;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Entities\Position;
use Modules\DataAcuan\Entities\Region;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\SalesOrder\Entities\SalesOrder;

class MarketingPoinV2 extends Model
{
    use HasFactory, Uuids, SoftDeletes, ChildrenList;

    protected $guarded = [];
    protected $table = "personels";
    protected $appends = [
        'sub_region',
        'region',
    ];

    public function area()
    {
        return $this->hasOne(MarketingAreaDistrict::class, "personel_id", "id")->with("subRegionWithRegion");
    }

    public function subRegion()
    {
        return $this->hasOne(SubRegion::class, "personel_id", "id")->with("region");
    }

    public function supervisorSubRegion()
    {
        return $this->hasOne(SubRegion::class, "personel_id", "supervisor_id")->with("region");
    }

    public function region()
    {
        return $this->hasOne(Region::class, "personel_id", "id")->with("provinceRegion");
    }

    public function hasManyRegion()
    {
        return $this->belongsTo(Region::class, "id", "personel_id")->with("provinceRegion");
    }

    public function position()
    {
        return $this->hasOne(Position::class, "id", "position_id");
    }

    public function getSubRegionAttribute()
    {
        $district = $this->area()->first();
        $sub = $this->subRegion()->first();
        $supervisor_sub_region = $this->supervisorSubRegion()->first();
        $sub_region = [];

        if ($district) {
            $district_list = $this->area()->get();
            foreach ($district_list as $district) {
                array_push($sub_region, $district->subRegionWithRegion);
            }
        } else if ($sub) {
            $sub_region = $this->subRegion()->get()->toArray();
        }
        /* pending */
        // $sub_region = array_unique($sub_region);
        $sub_region = collect($sub_region)->unique("id")->values();
        return $sub_region;
    }

    public function getRegionAttribute()
    {
        $district = $this->area()->first();
        $sub = $this->subRegion()->with("region")->first();
        $region_data = $this->hasManyRegion()->first();
        $region = [];

        if ($district) {
            $district = $this->area()->With([
                "subRegionWithRegion" => function ($QQQ) {
                    return $QQQ->with("region");
                },
            ])->get();
            $region = [];
            foreach ($district as $dist) {
                array_push($region, $dist->subRegionWithRegion->region);
            }
        } else if ($sub) {
            $sub = $this->subRegion()->with("region")->get();
            $region = [];
            foreach ($sub as $s) {
                array_push($region, $s->region);
            }
        } else if ($region_data) {
            $region = [];
            $region = $this->hasManyRegion()->get()->toArray();
        }

        $region = collect($region)
            ->unique("id")
            ->values()
            ->toArray();

        return $region;
    }

    public function salesOrder()
    {
        return $this->hasMany(SalesOrder::class, 'personel_id', 'id');
    }
}
