<?php

namespace Modules\Personel\Entities;

use App\Traits\MarketingArea;
use App\Traits\Uuids;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Entities\Region;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\Personel\Entities\Marketing;
use Modules\Personel\Entities\MarketingFeePayment;
use Modules\Personel\Entities\Personel;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MarketingFee extends Model
{
    use Uuids;
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;
    use MarketingArea;
    use CascadeSoftDeletes;

    protected $table = "marketing_fee";
    protected $cascadeDeletes = [
        "payment",
    ];
    protected $dates = ['deleted_at'];

    protected $casts = [
        "id" => "string",
    ];

    protected $guarded = [
        "created_at",
        "updated_at",
        "deleted_at",
    ];

    protected $appends = [
        'sub_region',
        'region',
    ];

    protected static function newFactory()
    {
        return \Modules\Personel\Database\factories\MarketingFeeFactory::new ();
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

    public function marketing()
    {
        return $this->hasOne(Marketing::class, "id", "personel_id");
    }

    public function personel()
    {
        return $this->hasOne(Personel::class, "id", "personel_id");
    }

    public function area()
    {
        return $this->hasOne(MarketingAreaDistrict::class, "personel_id", "personel_id")->with("subRegionWithRegion");
    }

    public function subRegion()
    {
        return $this->hasOne(SubRegion::class, "personel_id", "personel_id")->with("region");
    }

    public function supervisorSubRegion()
    {
        return $this->hasOne(SubRegion::class, "personel_id", "personel_id")->with("region");
    }

    public function region()
    {
        return $this->hasOne(Region::class, "personel_id", "personel_id")->with("provinceRegion");
    }

    public function hasManyRegion()
    {
        return $this->belongsTo(Region::class, "personel_id", "personel_id")->with("provinceRegion");
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
        $sub_region = collect($sub_region)->unique("id")->toArray();
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
                if (!empty($dist->subRegionWithRegion->region)) {
                    array_push($region, $dist->subRegionWithRegion->region);
                }
            }
        } else if ($sub) {
            $sub = $this->subRegion()->with("region")->get();
            $region = [];
            foreach ($sub as $s) {
                if (!empty($s->region)) {
                    array_push($region, $s->region);
                }
            }
        } else if ($region_data) {
            $region = [];
            $region = $this->hasManyRegion()->get()->toArray();
        }

        $region = collect($region)->unique("id")->toArray();
        return $region;
    }

    public function scopeByArea($query, $area_id)
    {
        $marketing_on_area = $this->marketingListByAreaId($area_id);
        return $query->whereIn("personel_id", $marketing_on_area);
    }

    public function payment()
    {
        return $this->hasMany(MarketingFeePayment::class, "marketing_fee_id", "id");
    }

    public function lastPayment()
    {
        return $this->hasOne(MarketingFeePayment::class, "marketing_fee_id", "id")
            ->orderBy("date", "desc");
    }
}
