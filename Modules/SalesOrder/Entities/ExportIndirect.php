<?php

namespace Modules\SalesOrder\Entities;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExportIndirect extends Model
{
    use HasFactory;

    protected $table = "export_indirect";

    protected $guarded = [];

    protected $appends = [
        "jumlahco",
        "month",
        "year"
    ];
    
    public function exportIndirectChild()
    {
        return $this->hasMany(ExportIndirectChild::class, "order_number", "order_number");
    }

    public function getjumlahcoAttribute()
    {
        $exportIndirect = $this->exportIndirectChild()->get();

        $qtyExportIndirect = $exportIndirect->sum("Qty_pesan");

        return $qtyExportIndirect;
    }

    public function getmonthAttribute()
    {
        return Carbon::parse($this->tgl_nota)->format("F");
    }

    public function getyearAttribute()
    {
        return Carbon::parse($this->tgl_nota)->format("Y");
    }
}
