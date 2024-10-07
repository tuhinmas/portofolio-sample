<?php

namespace Modules\Authentication\Entities;

use App\Traits\TimeSerilization;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Modules\Authentication\Entities\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Device extends Model
{
    use TimeSerilization;
    use HasFactory;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\Authentication\Database\factories\DeviceFactory::new();
    }

    public function user(){
        return $this->hasOne(User::class, "id", "user_id");
    }

    public function personel(){
        return $this->hasOneThrough(
            Personel::class, 
            User::class,
            "users.id",
            "personels.id",
            "user_id",
            "personel_id"
        );
    }
}
