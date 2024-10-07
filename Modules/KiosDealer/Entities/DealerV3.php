<?php

namespace Modules\KiosDealer\Entities;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Personel\Entities\Personel;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class DealerV3 extends Model {

    use SoftDeletes;
    use Uuids;
    // use LogsActivity;

    protected $table = "dealers";

    public function personel()
    {
        return $this->belongsTo(Personel::class, "personel_id", "id");
    }

    public function suggestedGrading()
    {
        return $this->hasOne(Grading::class, 'id', 'suggested_grading_id');
    }

    public function grading()
    {
        return $this->hasOne(Grading::class, 'id', 'grading_id');
    }

}