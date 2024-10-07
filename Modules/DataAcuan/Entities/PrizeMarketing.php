<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrizeMarketing extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;

    public $incrementing = false;

    protected $guarded = []; 
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\PrizeMarketingFactory::new();
    }
}
