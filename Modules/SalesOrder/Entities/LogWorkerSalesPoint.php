<?php

namespace Modules\SalesOrder\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LogWorkerSalesPoint extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\SalesOrder\Database\factories\LogWorkerSalesPointFactory::new();
    }
}
