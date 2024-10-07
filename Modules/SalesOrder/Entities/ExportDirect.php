<?php

namespace Modules\SalesOrder\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExportDirect extends Model
{
    use HasFactory;

    protected $table = "export_direct";

    protected $guarded = [];
    
}
