<?php

namespace Modules\Organisation\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Organisation\Entities\Organisation;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Holding extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;

    public $incrementing = false;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\Organisation\Database\factories\HoldingFactory::new();
    }

    public function organisation(){
        return $this->hasMany(Organisation::class,'holding_id','id');
    }

    // public function 
}
