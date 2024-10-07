<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StatusFee extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Uuids;

    protected $guarded = [];
    public $incrementing = false;
    protected $table = "status_fee";
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\StatusFeeFactory::new();
    }

    
}
