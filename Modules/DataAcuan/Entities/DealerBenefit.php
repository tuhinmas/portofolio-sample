<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\AgencyLevel;
use Modules\DataAcuan\Entities\Grading;
use Modules\DataAcuan\Entities\PaymentMethod;

class DealerBenefit extends Model
{
    use HasFactory, Uuids, SoftDeletes;
    protected $guarded = [];
    protected $casts = [
        'benefit_discount' => 'array',
        'agency_level_id' => 'array',
    ];
    protected $attributes = [
        "agency_level_id" => "{}",
        "benefit_discount" => "{}",
    ];

    protected $appends = [
        "agency_levels",
    ];

    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\DealerBenefitFactory::new ();
    }

    public function grading()
    {
        return $this->belongsTo(Grading::class, "grading_id", "id")->withTrashed();
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, "payment_method_id", "id");
    }

    public function getAgencyLevelsAttribute()
    {
        return AgencyLevel::query()
            ->whereIn("id", $this->agency_level_id)
            ->get();
    }
}
