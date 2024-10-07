<?php

namespace Modules\SalesOrder\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExportIndirectChild extends Model
{
    use HasFactory;

    protected $table = "export_indirect_child";

    protected $guarded = [];
    
}
