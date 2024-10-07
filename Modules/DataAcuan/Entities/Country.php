<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Country extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;

    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\CountryFactory::new();
    }

    // public function address(){
    //     return $this->belongsTo(Address::Class,);
    // }
}
