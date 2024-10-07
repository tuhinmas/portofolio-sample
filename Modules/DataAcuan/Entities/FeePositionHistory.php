<?php

namespace Modules\DataAcuan\Entities;

use App\Traits\TimeSerilization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class FeePositionHistory extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TimeSerilization;

    protected $casts = [
        "fee_position" => "json",
    ];

    protected $guarded = [
        "created_at",
        "updated_at",
        "deleted_at",
    ];

    protected $appends = [
        "date_end",
    ];

    protected static function newFactory()
    {
        return \Modules\DataAcuan\Database\factories\FeePositionHistoryFactory::new ();
    }

    public function getDateEndAttribute()
    {
        $next_data = DB::table('fee_position_histories')
            ->whereNull("deleted_at")
            ->whereDate("date_start", ">", $this->date_start)
            ->orderBy("date_start")
            ->first();

        return $next_data ? date_format(date_sub(date_create($next_data->date_start), date_interval_create_from_date_string("1 days")), "Y-m-d H:i:s") : null;
    }
}
