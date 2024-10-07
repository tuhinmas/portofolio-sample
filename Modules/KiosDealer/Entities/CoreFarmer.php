<?php

namespace Modules\KiosDealer\Entities;

use App\Traits\Uuids;
use Modules\KiosDealer\Entities\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CoreFarmer extends Model
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
        return $this->belongsTo(Store::class,'store_id','id');
    }

    public function storeTemp() {
        return $this->belongsTo(StoreTemp::class,'store_id','id');
    }
}
