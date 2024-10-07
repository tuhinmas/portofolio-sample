<?php

namespace Modules\Personel\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\DataAcuan\Entities\Position;
use Modules\DataAcuan\Entities\Region;
use Modules\DataAcuan\Entities\SubRegion;

class PersonelExport extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;

    protected $table = "personels";

    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];

    protected $appends = [
        "position_name",
        "region_name",
        "sub_region_name"
    ];

    public function position()
    {
        return $this->hasOne(Position::class, 'id', 'position_id');
    }


    public function subRegion()
    {
        return $this->hasOne(SubRegion::class, "personel_id", "id");
    }
    public function region()
    {
        return $this->hasOne(Region::class, "personel_id", "id");
    }

    public function getPositionNameAttribute()
    {
        $data = $this->position()->first();
        if ($data) {
            return $data->name;
        }
    }

    public function getRegionNameAttribute()
    {
        $data = $this->region()->first();
        if ($data) {
            return $data->name;
        }
    }

    public function getSubRegionNameAttribute()
    {
        $data = $this->subRegion()->first();
        if ($data) {
            return $data->name;
        }
    }
}
