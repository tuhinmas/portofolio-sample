<?php

namespace Modules\OneSignal\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Authentication\Entities\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserDevice extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\OneSignal\Database\factories\UserDeviceFactory::new();
    }

    public function user(){
        return $this->hasOne(User::class, "id", "user_id");
    }
}
