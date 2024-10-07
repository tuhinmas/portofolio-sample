<?php

namespace Modules\Authentication\Entities;

use App\Traits\Enums;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MobileVersion extends Model
{
    use HasFactory;
    use Enums;
    use SoftDeletes;

    protected $guarded = [
        "id",
        "created_at",
        "updated_at",
    ];

    protected $enumEnvironments = [
        "staging",
        "production"
    ];
    
    protected static function newFactory()
    {
        return \Modules\Authentication\Database\factories\MobileVersionFactory::new();
    }
}
