<?php

namespace Modules\Invoice\Entities;

use App\Traits\Uuids;
use Illuminate\Http\Request;
use Spatie\Activitylog\LogOptions;
use Modules\Invoice\Entities\Invoice;
use Illuminate\Database\Eloquent\Model;
use Modules\Personel\Entities\Personel;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\DataAcuan\Entities\ProformaReceipt;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceProforma extends Model
{
    use HasFactory, Uuids, SoftDeletes, LogsActivity;

    protected $guarded = [];
    protected $casts = [
        "id" => "string"
    ];
    public $incrementing = false;
    protected static function newFactory()
    {
        return \Modules\Invoice\Database\factories\InvoiceProformaFactory::new();
    }

    /**
     * activity logs set causer
     *
     * @param Activity $activity
     * @param string $eventName
     * @return void
     */
    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }

    /**
     * activity logs
     *
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["*"]);
        // Chain fluent methods for configuration options
    }
    

    public function personel(){
        return $this->hasOne(Personel::class, "id", "issued_by")->with("position");
    }

    public function invoice(){
        return $this->hasOne(Invoice::class, "id", "invoice_id");
    }

    public function receipt(){
        return $this->hasOne(ProformaReceipt::class, "id", "receipt_id");
    }

    public function confirmedBy(){
        return $this->hasOne(Personel::class, "id", "confirmed_by");
    }
}
