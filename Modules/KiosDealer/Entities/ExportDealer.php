<?php

namespace Modules\KiosDealer\Entities;

use Modules\Address\Entities\Address;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\DataAcuan\Entities\DealerPaymentMethod;
use Modules\DataAcuan\Entities\Entity;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrderV2\Entities\SalesOrderV2;

class ExportDealer extends Model
{
    use HasFactory;

    protected $fillable = [];
    protected $table = "export_dealers";
    protected $casts = [
        "id" => "string"
    ];
    protected $appends = [
      

    ];
}
