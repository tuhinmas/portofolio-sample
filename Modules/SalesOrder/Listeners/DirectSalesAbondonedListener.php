<?php

namespace Modules\SalesOrder\Listeners;

use App\Traits\ChildrenList;
use Carbon\Carbon;
use Modules\Authentication\Entities\User;
use Modules\ForeCast\Events\ForecastLockNotificationEvent;
use Modules\Forecast\Notifications\ForecastSubmission;
use Modules\Notification\Entities\Notification;
use Modules\SalesOrder\Events\DirectSalesAbandonedNotificationEvent;
use Modules\SalesOrder\Notifications\DirectSalesAbandonedSubmission;
use Modules\SalesOrderV2\Entities\SalesOrderV2;

class DirectSalesAbondonedListener
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
    public function handle(DirectSalesAbandonedNotificationEvent $event)
    {
        /**
         * notif if last sales order direct with 
         * status draft not sumbit until 10 days 
         */
        $direct_sales = SalesOrderV2::query()->where("type", "1")
            ->where("status", "draft")
            ->with(["notification" => function ($query) {
                return $query->where("notification_marketing_group_id", "1");
            }])
            ->where("personel_id", $event->user->personel_id)->get();

        foreach ($direct_sales as $data) {
            if (Carbon::now()->format("Y-m-d") >= Carbon::parse($data->updated_at)->endOfDay()->addDays(10)) {
                if (!$data->notification) {
                    $details = [
                        'personel_id' => $data->personel_id,
                        'notified_feature' => "sales_abondened",
                        'notification_text' => "Draft direct Sales Order Tanggal " . $data->updated_at,
                        'mobile_link' => "-",
                        'desktop_link' => "marketing-staff/sales-order/create/edit/" . $data->id,
                        'data_id' => $data->id,
                        'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
                        'as_marketing' => true,
                    ];

                    $external_data = [
                        "notification_marketing_group_id" => "1",
                        'personel_id' => $data->personel_id,
                        'notified_feature' => "sales_abondened",
                        'notification_text' => "Draft direct Sales Order Tanggal " . $data->updated_at,
                        'mobile_link' => "-",
                        'desktop_link' => "marketing-staff/sales-order/create/edit/" . $data->id,
                        'data_id' => $data->id,
                        'status' => $data->status,
                        'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
                        'as_marketing' => true,
                    ];

                    $member = User::withTrashed()->where("personel_id", $data->personel_id)->first();
                    if ($member) {
                        $member->notify(new DirectSalesAbandonedSubmission($details, $external_data));
                    }
                }
            }
        }

        $direct_sales_submit = SalesOrderV2::query()->where("type", "1")
            ->where("status", "submited")
            ->whereHas("invoiceOnly")
            ->with(["notification" => function ($query) {
                return $query->where("notification_marketing_group_id", "1");
            }])
            ->where("personel_id", $event->user->personel_id)->get();


        foreach ($direct_sales_submit as $data) {
            if (Carbon::now()->format("Y-m-d") >= Carbon::parse($data->updated_at)->endOfDay()->addDays(10)) {
                if (!$data->notification) {
                    $details = [
                        'personel_id' => $data->personel_id,
                        'notified_feature' => "sales_abondened_submit",
                        'notification_text' => "Submit direct Sales Order Tanggal " . $data->updated_at,
                        'mobile_link' => "-",
                        'desktop_link' => "marketing-staff/sales-order/create/edit/" . $data->id,
                        'data_id' => $data->id,
                        'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
                        'as_marketing' => true,
                    ];

                    $external_data = [
                        "notification_marketing_group_id" => "100",
                        'personel_id' => $data->personel_id,
                        'notified_feature' => "sales_abondened_submit",
                        'notification_text' => "Sumbit direct Sales Order Tanggal " . $data->updated_at,
                        'mobile_link' => "-",
                        'desktop_link' => "marketing-staff/sales-order/create/edit/" . $data->id,
                        'data_id' => $data->id,
                        'status' => "submited",
                        'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
                        'as_marketing' => true,
                    ];

                    $member = User::withTrashed()->where("personel_id", $data->personel_id)->first();
                    if ($member) {
                        $member->notify(new DirectSalesAbandonedSubmission($details, $external_data));
                    }
                }
            }
        }

        $notifications = Notification::where("notified_feature", "sales_abondened_submit")->where("personel_id", $event->user->personel_id)->count();
        Notification::updateOrCreate([
            "created_at" => now(),
            'notification_text' => "Terdapat " . $notifications . " Direct Sales Order",
            'mobile_link' => "-",
            'expired_at' => Carbon::createFromFormat('Y-m-d H:i:s', Carbon::now(), 'UTC')->addDays(30)->setTimezone('Asia/Jakarta'),
            'as_marketing' => true
        ], [
            "notification_marketing_group_id" => "1",
            "personel_id" => $event->user->personel_id,
            "notified_feature" => "sales_abondened_submit_count",
            "status" => "submited"
        ]);
    }
}
