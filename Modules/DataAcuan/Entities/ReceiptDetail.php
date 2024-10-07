<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReceiptDetail extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Uuids;

    protected $table = "receipts_details";
    protected $casts = [
        'note' => 'array',
    ];
    
    protected $guarded = [
        "created_at",
        "updated_at",
        "deleted_at"
    ];

    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\ReceiptDetailFactory::new();
    }
}
