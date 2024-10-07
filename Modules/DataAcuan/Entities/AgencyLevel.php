<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Modules\DataAcuan\Entities\Price;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\DealerAgencyLevelChangeLog\Entities\DealerAgencyLevelChangeLog;

class AgencyLevel extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;

    public $incrementing = false;

    protected $guarded = [];
    public $timestamps = false;
    protected $table = 'agency_levels';
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\AgencyLevelFactory::new();
    }

    public function price(){
        return $this->hasMany(Price::class,'agency_level_id','id');
    }

    public function agencyLevel(){
        return $this->hasMany(DealerAgencyLevelChangeLog::class,'agency_level_id','id');
    }
}
