<?php

namespace Modules\ReceivingGood\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReceivingGoodReceived extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\ReceivingGood\Database\factories\ReceivingGoodReceivedFactory::new();
    }
}
