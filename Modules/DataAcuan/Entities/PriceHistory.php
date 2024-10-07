<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriceHistory extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;

    public $incrementing = false;

    protected $guarded = []; 
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\PriceHistoryFactory::new();
    }

    public function priceBelongTo(){
        return $this->belongsTo(Price::class,'price_id','id');
    } 

    public function product(){
        return $this->belongsTo(Product::class,'product_id','id');
    } 

    public function agencyLevel(){
        return $this->belongsTo(AgencyLevel::class,'agency_level_id','id');
    }
}
