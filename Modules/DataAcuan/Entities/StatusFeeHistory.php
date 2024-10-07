<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\TimeSerilization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class StatusFeeHistory extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TimeSerilization;

    protected $guarded = [];
    protected $casts = [
        "status_fee" => "json",
    ];

    protected $appends = [
        "date_end",
    ];

    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\StatusFeeHistoryFactory::new ();
    }

    public function getDateEndAttribute()
    {
        $next_data = DB::table('status_fee_histories')
            ->whereNull("deleted_at")
            ->whereDate("date_start", ">", $this->date_start)
            ->orderBy("date_start")
            ->first();

        return $next_data ? date_format(date_sub(date_create($next_data->date_start), date_interval_create_from_date_string("1 days")), "Y-m-d H:i:s") : null;
    }

    public function toArray()
    {
        $array = parent::toArray();
        $orderedAttributes = [
            "id",
            "date_start",
            "date_end",
            "status_fee",
            "is_checked",
            "created_at",
            "updated_at",
            "deleted_at",
        ];

        return array_merge(array_flip($orderedAttributes), $array);
    }
}
