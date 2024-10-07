<?php

namespace Modules\KiosDealer\Entities;

use App\Traits\Uuids;
use Modules\Address\Entities\Address;
use Modules\DataAcuan\Entities\Entity;
use Illuminate\Database\Eloquent\Model;
use Modules\DataAcuan\Entities\Grading;
use Modules\Personel\Entities\Personel;
use Modules\KiosDealer\Entities\Handover;
use Modules\DataAcuan\Entities\AgencyLevel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\KiosDealer\Entities\DealerFileTemp;
use Modules\KiosDealer\Entities\SubDistributorFile;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubDistributor extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Uuids;

    protected $guarded = [];
    protected $table = "sub_dealers";
    protected $casts = [
        "id" => "string"
    ];
    public $incrementing = false;

      /**
     * Scope a query to only include popular users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereName($query, $name)
    {
        return $query->where('name', 'like', $name);
    }
    
    protected static function newFactory()
    {
        return \Modules\KiosDealer\Database\factories\DealerTempFactory::new();
    }

    public function adress_detail(){
        return $this->hasMany(Address::class, "parent_id", "id")->with("province", "city", "district");
    }

    public function grading()
    {
        return $this->hasOne(Grading::class, 'id', 'grading_id');
    }

    public function personel()
    {
        return $this->belongsTo(Personel::class, 'personel_id', 'id');
    }

    public function agencyLevel()
    {
        return $this->belongsTo(AgencyLevel::class, 'agency_level_id', 'id');
    }

    public function entity()
    {
        return $this->hasOne(Entity::class, 'id', 'entity_id');
    }

    public function handover()
    {
        return $this->hasOne(Handover::class, 'id', 'handover_status');
    }
}
