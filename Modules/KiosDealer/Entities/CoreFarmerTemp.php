<?php

namespace Modules\KiosDealer\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Modules\KiosDealer\Entities\StoreTemp;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoreFarmerTemp extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;

    public $incrementing = false;
    protected $guarded = [];

    protected static function newFactory()
    {
        return \Modules\KiosDealer\Database\factories\CoreFarmerFactory::new();
    }
    public function store(){
        return $this->belongsTo(StoreTemp::Class,'store_temp_id','id');
    }
}
