<?php

namespace Modules\Personel\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Address\Entities\District;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Entities\Region;
use Modules\DataAcuan\Entities\SubRegion;

class PersonelSimple extends Model
{
    use Uuids, SoftDeletes;

    protected $table = "personels";

    protected $guarded = [];

    public function regions()
    {
        return $this->hasMany(Region::class, "personel_id");
    }
    
    public function subRegions()
    {
        return $this->hasMany(SubRegion::class, "personel_id");
    }

    public function districts()
    {
        return $this->hasMany(MarketingAreaDistrict::class, "personel_id");
    }

}
