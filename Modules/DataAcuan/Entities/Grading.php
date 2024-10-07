<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\TimeSerilization;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Modules\KiosDealer\Entities\Dealer;

class Grading extends Model
{
    use HasFactory, SoftDeletes, TimeSerilization;
    protected $guarded = [];
    protected $casts = [
        'action' => 'array',
    ];

    protected $appends = [
        'active_benefit',
        'is_used',
    ];
    protected $attributes = ["action" => '{}'];
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\GradingFactory::new ();
    }
    public function dealer()
    {
        return $this->hasMany(Dealer::class, "grading_id", "id");
    }

    public function benefit()
    {
        return $this->hasMany(DealerBenefit::class, "grading_id", "id");
    }

    public function benefitActivePeriod()
    {
        $now = Carbon::now();
        return $this->hasOne(DealerBenefit::class, "grading_id", "id")->where("start_period", "<=", $now)->where("end_period", ">=", $now);
    }

    public function benefitDefault()
    {
        return $this->hasOne(DealerBenefit::class, "grading_id", "id")->whereNull("start_period");
    }

    public function getActiveBenefitAttribute()
    {
        $ben = null;
        $benefit = $this->benefitActivePeriod()->first();
        $benefit_default = $this->benefitDefault()->first();
        if ($benefit) {
            $ben = $benefit;
        } else {
            $ben = $benefit_default;
        }
        return $ben;

    }

    public function getIsUsedAttribute()
    {
        $dealer = DB::table('dealers')->where("grading_id", $this->id)->first();
        return $dealer ? true : false;
    }
}
