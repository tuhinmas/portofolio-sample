<?php

namespace Modules\DistributionChannel\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeliveryOrderHistory extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\DistributionChannel\Database\factories\DeliveryOrderHistoryFactory::new();
    }
}
