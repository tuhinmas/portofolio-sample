<?php

namespace Modules\Personel\Entities;

use App\Traits\Enums;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LogFreeze extends Model
{
    use Uuids;
    use Enums;
    use HasFactory;
    use SoftDeletes;

    protected $enumStatuses = [1,2,3];
    protected $table = "log_freeze";
    protected $guarded = [
        "created_at",
        "updated_at"
    ];
    
    protected static function newFactory()
    {
        return \Modules\Personel\Database\factories\LogFreezeFactory::new();
    }
}
