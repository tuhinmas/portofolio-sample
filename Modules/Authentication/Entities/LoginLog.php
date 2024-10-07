<?php

namespace Modules\Authentication\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoginLog extends Model
{
    use HasFactory;

    protected $fillable = ["user_id", "date", "token", "logout_at", "login_at"];
    protected $table = "log_logins";
    protected static function newFactory()
    {
        return \Modules\Authentication\Database\factories\LoginLogFactory::new();
    }

}
