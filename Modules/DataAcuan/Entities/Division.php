<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Division;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Traits\SelfReferenceTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Division extends Model
{
    use HasFactory;
    use Uuids;
    use SelfReferenceTrait;
    use SoftDeletes;

    public $incrementing = false;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\DivisionFactory::new();
    }

    public function induk_divisi(){
        return $this->hasOne(Division::class,'id','parent_id');
    }
}
