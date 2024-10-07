<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\Enums;
use App\Traits\SuperVisorCheckV2;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Event\Entities\EventType;

class BudgetRule extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Uuids;
    use SuperVisorCheckV2;
    use Enums;

    protected $guarded = [];

    public function scopeSearch($query, $search)
    {
        return $query->when($search, function($query) use ($search){
            return $query->whereHas('budget', function($query) use ($search) {
               return $query->where('name','like',"%{$search}%");
            })->orWhereHas('budgetArea', function($query) use ($search) {
                return $query->where('group_name','like',"%{$search}%");
             });
        });
    }

    public function event()
    {
        return $this->belongsTo(EventType::class,'id_event', 'id');
    }

    public function budgetArea()
    {
        return $this->belongsTo(BudgetArea::class,'id_budget_area', 'id');
    }

    public function budget()
    {
        return $this->belongsTo(Budget::class,'id_budget', 'id');
    }


    protected $enumType_Budgets = [
        1,
        2,
        3,
        4
    ];
    
    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\BudgetRuleFactory::new();
    }
}
