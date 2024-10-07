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
use Ladumor\OneSignal\OneSignal;
use Modules\Authentication\Entities\User;

class InvoiceMobileNotificationJob implements ShouldQueue
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
        $notifiables->load([
            "userDevices",
        ]);

        $devices = [];
        if ($notifiables instanceof Collection) {
            $devices = $users
                ->map(function ($q) {
                    return $q
                        ->userDevices
                        ->map(function ($q) {
                            return $q->os_player_id;
                        })
                        ->toArray();
                })
                ->flatten()
                ->toArray();
        } else {
            $devices = $notifiables->userDevices->pluck("os_player_id")->toArray();
        }

        $fields = [
            "include_player_ids" => $devices,
            "data" => [
                "subtitle" => $notification_text,
                "menu" => "direct_sales",
                "data_id" => $data->id,
                "mobile_link" => $mobile_link,
                "desktop_link" => $desktop_link,
                "notification" => $notification_text,
                "is_supervisor" => false,
            ],
            "contents" => [
                "en" => $notification_text,
                "in" => $notification_text,
            ],
            "recipients" => 1,
        ];

        OneSignal::sendPush($fields, $notification_text);
    }
}
