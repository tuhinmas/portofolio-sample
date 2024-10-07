<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Organisation\Entities\Organisation;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Contact extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;


    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
    public function organisation(){
        return $this->hasOne(Organisation::class,'contact_id','id');
    }

    public function personel(){
        return $this->hasOne(Personel::class,'contact_id','id');
    }
}
