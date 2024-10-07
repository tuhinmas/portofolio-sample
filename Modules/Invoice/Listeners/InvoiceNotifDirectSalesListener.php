<?php

namespace Modules\Invoice\Listeners;

use App\Traits\ChildrenList;
use Carbon\Carbon;
use Modules\Authentication\Entities\User;
use Modules\Invoice\Events\InvoiceNotifDirectSalesEvent;
use Modules\Invoice\Notifications\DirectSalesSubmission;

class InvoiceNotifDirectSalesListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */

    use ChildrenList;
    
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(InvoiceNotifDirectSalesEvent $event)
    {
        /**
         * prize will add to budget plan if there
         * does not exist in budget plan
         */
        $data = $event->invoice->with("user.personel")->with("salesOrderOnly")->find($event->invoice->id);

        /* order before last order */
        $details = [
            'personel_id' => $event->invoice->salesOrderOnly->personel_id,
            'notified_feature' => "direct_sales",
            'notification_text' => "Direct sales  No. ".$event->invoice->invoice." telah dikonfirmasi support",
            'mobile_link' => "/DetailProformaDirectOrderPage",
            'desktop_link' => "/marketing-staff/invoice-detail/detail/".$event->invoice->id."/invoice-detail",
            'data_id' => $event->invoice->id,
            "status" => "confirmed",
            'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
            'as_marketing' => true
        ];

        $member = User::withTrashed()->where("personel_id", $event->invoice->salesOrderOnly->personel_id)->first();
        if ($member) {

          
            $member->notify(new DirectSalesSubmission($details));
            $notification = $member->notifications->first();
            $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
            $notification->notification_marketing_group_id = "1";
            $notification->notified_feature = "direct_sales";
            $notification->notification_text = "Direct sales  No. ".$event->invoice->invoice." telah dikonfirmasi support";
            $notification->mobile_link = "/DetailProformaDirectOrderPage";
            $notification->desktop_link = "/marketing-staff/invoice-detail/detail/".$event->invoice->id."/invoice-detail";
            $notification->as_marketing = true;
            $notification->status = "confirmed";
            $notification->data_id = $event->invoice->id;
            $notification->save();
        }
        
        $marketing_supervisor = $this->parentPersonel($event->invoice->salesOrderOnly->personel_id);
       
        foreach ($marketing_supervisor as $key => $value) {
            $member = User::withTrashed()->where("personel_id", $value)->first();
            if ($member) {
                $member->notify(new DirectSalesSubmission($details));

                $notification = $member->notifications->first();
                $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
                $notification->notification_marketing_group_id = "1";
                $notification->notified_feature = "direct_sales";
                $notification->notification_text = "Direct sales  No. ".$event->invoice->invoice." telah dikonfirmasi support";
                $notification->mobile_link = "/DetailProformaDirectOrderPage";
                $notification->desktop_link = "/marketing-staff/invoice-detail/detail/".$event->invoice->id."/invoice-detail";
                $notification->as_marketing = false;
                $notification->status = "confirmed";
                $notification->personel_id = $event->invoice->salesOrderOnly->personel_id;
                $notification->data_id = $event->invoice->id;
                $notification->save();
            }
        }

    }
}