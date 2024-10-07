<?php

namespace Modules\Personel\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PersonelSupervisorHistory extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\Personel\Database\factories\PersonelSupervisorHistoryFactory::new();
    }

    public function personel(){
        return $this->hasOne(Personel::class, "id", "personel_id");
    }

    public function supervisor(){
        return $this->hasOne(Personel::class, "id", "supervisor_id");
    }
}
