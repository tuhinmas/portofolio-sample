<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\TimeSerilization;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Modules\DataAcuan\Entities\MaxDaysReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LogMaxDaysReference extends Model
{
    use HasFactory;
    use TimeSerilization;

    protected $guarded = [];

    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\LogMaxDaysReferenceFactory::new ();
    }

    public function personel()
    {
        return $this->hasOne(Personel::class, "id", "personel_id");
    }

    public function maxDays(){
        return $this->hasOne(MaxDaysReference::class, "id", "max_days_reference_id");
    }
}
