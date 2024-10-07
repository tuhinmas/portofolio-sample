<?php

namespace Modules\Organisation\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\BussinessSector;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BussinessOrganisation extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;

    public $incrementing = false;
    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\Organisation\Database\factories\BussinessOrganisationFactory::new();
    }

    public function bussiness_sector(){
        return $this->belongsToMany(BussinessSector::class,'bussiness_organisations');
    }
}
