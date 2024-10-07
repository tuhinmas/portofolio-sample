<?php

namespace Modules\DistributionChannel\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DeliveryOrderNumber extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\DistributionChannel\Database\factories\DeliveryOrderNumberFactory::new();
    }
}
