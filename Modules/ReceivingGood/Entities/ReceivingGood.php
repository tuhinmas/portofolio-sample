<?php

namespace Modules\ReceivingGood\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Dyrynda\Database\Support\CascadeSoftDeletes;
use Modules\ReceivingGood\Entities\ReceivingGoodFile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\Personel\Entities\Personel;
use Modules\ReceivingGood\Entities\ReceivingGoodDetail;

class ReceivingGood extends Model
{
    use CascadeSoftDeletes;
    use HasFactory;
    use SoftDeletes;
    use Uuids;

    public $incrementing = false;
    protected $cascadeDeletes = [
        "receivingGoodDetail",
        "receivingGoodFile"
    ];
    protected $dates = ['deleted_at'];

    protected $casts = [
        "id" => "string",
    ];

    protected $guarded = [];

    protected static function newFactory()
    {
        return \Modules\ReceivingGood\Database\factories\ReceivingGoodFactory::new();
    }

    public function receivingGoodDetail()
    {
        return $this->hasMany(ReceivingGoodDetail::class, "receiving_good_id", "id");
    }

    public function deliveryOrder()
    {
        return $this->hasOne(DeliveryOrder::class, "id", "delivery_order_id")->where("status", "send");
    }

    public function receivingGoodFile()
    {
        return $this->hasMany(ReceivingGoodFile::class, "receiving_good_id", "id");
    }

    public function receivedBy()
    {
        return $this->hasOne(Personel::class, "id", "received_by");
    }

    public function invoiceHasOne()
    {
        return $this->hasOneThrough(
            DispatchOrder::class,
            DeliveryOrder::class,
            'id',
            'id',
            'delivery_order_id',
            'dispatch_order_id'
        )->with('invoice');
    }

    // protected static function boot()
    // {
    //     parent::boot();
    //     static::creating(function ($model) {
    //         if (empty($model->{$model->getKeyName()})) {
    //             $model->{$model->getKeyName()} = $model->delivery_order_id;
    //         }
    //     });
    // }
}
