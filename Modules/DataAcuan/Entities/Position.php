<?php

namespace Modules\DataAcuan\Entities;

use App\Models\Role;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\Division;
use Modules\DataAcuan\Entities\FeePosition;

class Position extends Model
{
    use HasFactory;
    use Uuids;
    use SoftDeletes;

    public $incrementing = false;

    protected $guarded = [];

    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\PositionFactory::new ();
    }

    public function division()
    {
        return $this->belongsTo(Division::class, 'division_id', 'id');
    }

    public function role()
    {
        return $this->hasOne(Role::class, "name", "name");
    }

    public function scopeMarketing($query)
    {
        return $query->whereIn('name', ['Regional Marketing (RM)', "Regional Marketing Coordinator (RMC)", "Marketing District Manager (MDM)", "Marketing Manager (MM)"]);
    }

    public function fee()
    {
        return $this->hasOne(FeePosition::class, "position_id", "id");
    }

    public function scopeEventCreator($query)
    {
        return $query->whereIn("name", [
            "Aplikator",
            "Regional Marketing (RM)",
            "Regional Marketing Coordinator (RMC)",
            "Marketing District Manager (MDM)",
            "Marketing Manager (MM)",
            "Assistant MDM",
        ]);
    }

    public function scopeApplicator($query)
    {
        return $query->whereIn("name", applicator_positions());
    }

    public function scopeMarketingManager($query)
    {
        return $query->where("is_mm", true);
    }
}
