<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogWorkerPointMarketing extends Model
{
    use HasFactory;

    protected $table = 'log_worker_point_marketing';
    protected $fillable =[
        "sales_order_id",
    ];
    
}
