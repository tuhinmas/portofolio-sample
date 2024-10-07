<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Authentication\Entities\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = "activity_log";
    public function user(){
        return $this->hasOne(User::class,"id", "causer_id");
    }
}
