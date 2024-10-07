<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Modules\KiosDealer\Entities\Dealer;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DealerPaymentMethod extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\DealerPaymentMethodFactory::new();
    }

    public function dealer(){
        return $this->belongsTo(Dealer::class, "dealer_id", "id");
    }

    public function paymentMethod(){
        return $this->belongsTo(PaymentMethod::class, "payment_method_id", "id");
    }
}
