<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\SuperVisorCheckV2;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketingPoin extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Uuids;
    use SuperVisorCheckV2;

    protected $table = "marketing_poin";

    protected $guarded = [];
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\MarketingPoinFactory::new();
    }

    public function product(){
        return $this->belongsTo(Product::class,'product_id', 'id');
    }
}
