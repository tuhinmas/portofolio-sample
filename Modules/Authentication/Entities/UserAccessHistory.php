<?php

namespace Modules\Authentication\Entities;

use App\Traits\TimeSerilization;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Modules\Authentication\Entities\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserAccessHistory extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TimeSerilization;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\Authentication\Database\factories\UserAccessHistoryFactory::new();
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
