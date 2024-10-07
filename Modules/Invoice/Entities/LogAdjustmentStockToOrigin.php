<?php

namespace Modules\Invoice\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LogAdjustmentStockToOrigin extends Model
{
    use HasFactory;
    use Uuids;

    protected $guarded = [
        "created_at",
        "updated_at"
    ];
    
    protected static function newFactory()
    {
        return \Modules\Invoice\Database\factories\LogAdjustmentStockToOriginFactory::new();
    }
}
