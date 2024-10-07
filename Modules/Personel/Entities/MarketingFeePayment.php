<?php

namespace Modules\Personel\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Entities\MarketingFee;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Personel\Entities\MarketingFeeFile;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MarketingFeePayment extends Model
{
    use CascadeSoftDeletes;
    use SoftDeletes;
    use HasFactory;
    use Uuids;


    protected $cascadeDeletes = [
        "files"
    ];
    protected $dates = ['deleted_at'];


    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\Personel\Database\factories\MarketingFeePaymentFactory::new();
    }

    public function marketingFee(){
        return $this->belongsTo(MarketingFee::class, "marketing_fee_id", "id");
    }

    public function files()
    {
        return $this->hasMany(MarketingFeeFile::class, "marketing_fee_payment_id", "id");
    }

    public function personel()
    {
        return $this->hasOne(Personel::class, "id", "personel_id");
    }
}
