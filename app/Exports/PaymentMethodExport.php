<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Modules\DataAcuan\Entities\PaymentMethod;
use Maatwebsite\Excel\Concerns\FromCollection;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PaymentMethodExport implements FromCollection
{
    use Exportable;
    use LogsActivity;
    
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return PaymentMethod::all();
    }

    public function tapActivity(Activity $activity, string $eventName)
    {
        $activity->causer_id = auth()->id();
    }
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(["*"]);
        // Chain fluent methods for configuration options
    }
}
