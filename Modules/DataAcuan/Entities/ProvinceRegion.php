<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\TimeSerilization;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\SubRegion;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProvinceRegion extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TimeSerilization;
    
    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\ProvinceRegionFactory::new();
    }

    public function subRegion(){
        return $this->hasMany(SubRegion::class, "region_id", "region_id");
    }
   
    public function district(){
        return $this->hasMany(SubRegion::class, "province_id", "province_id");
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        // static::deleted(function($province) {
        //     $province->district()->delete();
        // });
    }
}
