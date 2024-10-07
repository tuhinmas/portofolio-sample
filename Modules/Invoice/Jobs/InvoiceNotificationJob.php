<?php

namespace Modules\Invoice\Jobs;

use App\Traits\ChildrenList;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Modules\Authentication\Entities\User;
use Modules\Invoice\Notifications\InvoiceNotification;

class InvoiceNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use ChildrenList;

    public $deleteWhenMissingModels = true;
    protected $invoice;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 700;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 25;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 5;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($invoice)
    {
        $this->invoice = $invoice;
        $this->onQueue('order');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->invoice->load([
            "salesOrder.dealer",
            "user.personel.position",
            "salesOrder.personel.user",
        ]);

        $notification_text = "Direct sales  No. "
        . $this->invoice->salesOrder->order_number
        . ", toko "
        . $this->invoice->salesOrder->dealer->name
        . ", telah dikonfirmasi oleh "
        . $this->invoice->user?->personel?->name
        . ", "
        . $this->invoice->user?->personel?->position?->name;

        $mobile_link = "/DetailProformaDirectOrderPage";
        $desktop_link = "/marketing-staff/invoice-detail/detail/" . $this->invoice->id . "/invoice-detail";

        /**
         * sne dnotif to marketing that order
         */
        self::notif($this->invoice->salesOrder?->personel?->user, $this->invoice, $notification_text, $mobile_link, $desktop_link);
    }

    public function middleware()
    {
        return [new WithoutOverlapping($this->invoice->sales_order_id)];
    }

    public function uniqueFor()
    {
        return 60; // Time in seconds
    }

    private function notif($notifiables, $data, $notification_text, $mobile_link, $desktop_link)
    {
        $details = [
            'notified_feature' => "direct_sales",
            'notification_text' => $notification_text,
            'mobile_link' => $mobile_link,
            'desktop_link' => $desktop_link,
            "data_id" => $data->id,
            'as_marketing' => true,
        ];

        $external_data = [
            "notification_marketing_group_id" => 1,
            'notified_feature' => "direct_sales",
            "notification_text" => $notification_text,
            "mobile_link" => $mobile_link,
            "desktop_link" => $desktop_link,
            "as_marketing" => true,
            "status" => $data->salesOrder->status,
            "data_id" => $data->id,
            "personel_id" => $data->salesOrder->personel_id,
        ];

        if ($notifiables instanceof Collection) {
            foreach ($notifiables as $user) {
                if ($data->salesOrder->personel_id != $user->personel_id) {
                    $details["as_marketing"] = false;
                    $external_data["as_marketing"] = false;
                } else {
                    continue;
                }
                $user->notify(new InvoiceNotification($details, $external_data));
            }
            return;
        }

        $notifiables->notify(new InvoiceNotification($details, $external_data));
    }
}
