<?php

namespace Modules\Invoice\ClassHelper;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentTimeForFee
{
    public static function paymentTimeForFeeCalculation($invoice): int
    {
        $fee_position = DB::table('fee_positions')
            ->whereNull("deleted_at")
            ->first();

        /* payment time start from date confirmation */
        if ($fee_position->settle_from == "2") {
            return $invoice->payment_time;
        }
        
        /* last payment is less then end of quarter */
        if ($invoice->last_payment != "-") {
            if (Carbon::parse($invoice->last_payment) <= $invoice->created_at->endOfQuarter()) {
                return 0;
            } else {
                return $invoice->created_at->endOfQuarter()->diffInDays(Carbon::parse($invoice->last_payment), false);
            }
        } else {
            return $invoice->created_at->diffInDays(now(), false);
        }
    }
}
