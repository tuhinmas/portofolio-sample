<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Enums;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProformaReceipt extends Model
{
    use HasFactory, SoftDeletes;
    use Enums;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\ProformaReceiptFactory::new();
    }

    public function confirmedBy(){
        return $this->hasOne(Personel::class, "id", "confirmed_by");
    }
}
