<?php

namespace Modules\Personel\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Personel\Entities\MarketingFeePayment;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MarketingFeeFile extends Model
{
    use SoftDeletes;
    use HasFactory;
    use Uuids;
    
    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\Personel\Database\factories\MarketingFeeFileFactory::new();
    }

    public function marketingFeePayment()
    {
        return $this->belongsTo(MarketingFeePayment::class, "marketing_fee_payment_id", "id");
    }
}
