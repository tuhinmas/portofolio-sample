<?php

namespace Modules\SalesOrder\Listeners;

use App\Traits\ChildrenList;
use Carbon\Carbon;
use Modules\Authentication\Entities\User;
use Modules\Personel\Entities\Personel;
use Modules\SalesOrder\Events\DirectSalesRejectedNotificationEvent;
use Modules\SalesOrder\Notifications\DirectSalesSubmission;

class SalesOrderDirectRejectedListener
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
    public function handle(DirectSalesRejectedNotificationEvent $event)
    {
        /**
         * prize will add to budget plan if there
         * does not exist in budget plan
         */

        $personel_detail = Personel::query()
            ->with([
                "areaMarketing" => function ($Q) {
                    return $Q->with([
                        "subRegionWithRegion" => function ($Q) {
                            return $Q->with([
                                "region",
                            ]);
                        },
                    ]);
                },
            ])
            ->where('id', $event->sales_order->personel_id)
            ->first();

        /* order before last order */
        $area = null;
        if ($personel_detail) {
            $area = $personel_detail->areaMarketing ? $personel_detail->areaMarketing->subRegionWithRegion : "-";
        }
        
        $details = [
            'personel_id' => $event->sales_order->personel_id,
            'notified_feature' => "direct_sales",
            'notification_text' => "Direct sales  No. " . $event->sales_order->order_number . " dibatalkan support",
            'mobile_link' => "/DetailOrderHistoryPage",
            'desktop_link' => "/marketing-staff/sales-order",
            'data_id' => $event->sales_order->id,
            'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
            'as_marketing' => true,
            'area' => $area,
        ];

        $member = User::withTrashed()->where("personel_id", $event->sales_order->personel_id)->first();

        if ($member) {
            $member->notify(new DirectSalesSubmission($details));
            $notification = $member->notifications->first();
            $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
            $notification->notification_marketing_group_id = "1";
            $notification->notified_feature = "direct_sales";
            $notification->notification_text = "Direct sales  No. " . $event->sales_order->order_number . " dibatalkan support";
            $notification->mobile_link = "/DetailOrderHistoryPage";
            $notification->desktop_link = "/marketing-staff/sales-order";
            $notification->as_marketing = true;
            $notification->status = "canceled";
            $notification->data_id = $event->sales_order->id;

            $notification->save();
        }

        $marketing_supervisor = $this->parentPersonel($event->sales_order->personel_id);
        foreach ($marketing_supervisor as $key => $value) {
            $member = User::withTrashed()->where("personel_id", $value)->first();
            if ($member) {
                $member->notify(new DirectSalesSubmission($details));

                $notification = $member->notifications->first();
                $notification->expired_at = Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta');
                $notification->notification_marketing_group_id = "1";
                $notification->notified_feature = "direct_sales";
                $notification->notification_text = "Direct sales  No. " . $event->sales_order->order_number . " dibatalkan support";
                $notification->mobile_link = "/DetailOrderHistoryPage";
                $notification->desktop_link = "/marketing-staff/sales-order";
                $notification->as_marketing = false;
                $notification->personel_id = $event->sales_order->personel_id;
                $notification->status = "canceled";
                $notification->data_id = $event->sales_order->id;

                $notification->save();
            }
        }
    }
}
