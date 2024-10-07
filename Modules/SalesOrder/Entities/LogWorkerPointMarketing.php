<?php

namespace Modules\SalesOrder\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LogWorkerPointMarketing extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = "log_worker_point_marketing";
    
    protected static function newFactory()
    {
        return \Modules\SalesOrder\Database\factories\LogWorkerPointMarketingFactory::new();
    }
}
