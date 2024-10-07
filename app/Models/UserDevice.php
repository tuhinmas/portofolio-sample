<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Authentication\Entities\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserDevice extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user(){
        return $this->hasOne(User::class, "id", "user_id");
    }
}
