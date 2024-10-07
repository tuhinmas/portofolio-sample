<?php

namespace Modules\SalesOrder\Listeners;

use App\Traits\ChildrenList;
use Carbon\Carbon;
use Modules\Authentication\Entities\User;
use Modules\Invoice\Notifications\DirectSalesSubmission;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrder\Events\DirectSalesConfirmedNotificationEvent;
use Modules\SalesOrder\Notifications\NewDirectSales;

class SalesOrderDirectConfirmedListener
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
    public function handle(DirectSalesConfirmedNotificationEvent $event)
    {
        /**
         * prize will add to budget plan if there
         * does not exist in budget plan
         */

         $personel_detail = Personel::where('id', $event->sales_order->personel_id)->with([
            "areaMarketing" => function ($Q) {
                return $Q->with([
                    "subRegionWithRegion" => function ($Q) {
                        return $Q->with([
                            "region",
                        ]);
                    },
                ]);
            },
        ])->first();

        /* order before last order */

        $notif = $personel_detail->areaMarketing ? $personel_detail->areaMarketing->subRegionWithRegion : "-";
        
        // $proforma_id = $event->sales_order->with("");

        $details = [
            'personel_id' => $event->sales_order->personel_id,
            'notified_feature' => "direct_sales",
            'notification_text' => "Direct sales  No. ".$event->sales_order->order_number." telah dikonfirmasi support",
            'mobile_link' => "/DetailProformaDirectOrderPage",
            'desktop_link' => "/marketing-staff/invoice-detail/detail/".$event->sales_order->id."/invoice-detail",
            'data_id' => $event->sales_order->id,
            'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
            'as_marketing' => true,
            'area' => $notif
        ];

        // $member = User::where("personel_id", $event->sales_orderv2->personel_id)->first();
        $member = User::withTrashed()->where("personel_id", $event->sales_order->personel_id)->first();
        if ($member) {
            $member->notify(new DirectSalesSubmission($details));  
            $notification = $member->notifications->first();
            $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
            $notification->notification_marketing_group_id = "1";
            $notification->notified_feature = "direct_sales";
            $notification->notification_text = "Direct sales  No. ".$event->sales_order->order_number." telah dikonfirmasi support";
            $notification->mobile_link = "/DetailProformaDirectOrderPage";
            $notification->desktop_link = "/marketing-staff/invoice-detail/detail/".$event->sales_order->id."/invoice-detail";
            $notification->as_marketing = true;
            $notification->status = "confirmed";
            $notification->data_id = $event->sales_order->id;

            $notification->save();
    
        }

        if ($member) {
            $member->notify(new DirectSalesSubmission($details));  
            $notification = $member->notifications->first();
            $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
            $notification->notification_marketing_group_id = "12";
            $notification->notified_feature = "direct_sales";
            $notification->notification_text = "Direct sales  No. ".$event->sales_order->order_number." telah dikonfirmasi support";
            $notification->mobile_link = "/DetailProformaDirectOrderPage";
            $notification->desktop_link = "/marketing-staff/invoice-detail/detail/".$event->sales_order->id."/invoice-detail";
            $notification->as_marketing = true;
            $notification->status = "confirmed";
            $notification->data_id = $event->sales_order->id;

            $notification->save();
    
        }
        
    }
}
