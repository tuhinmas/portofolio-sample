<?php

namespace Modules\Notification\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Authentication\Entities\User;
use Modules\Invoice\Entities\Invoice;
use Modules\Notification\Entities\NotificationMarketingGroup;

class NotificationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\Notification\Entities\Notification::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $user = User::factory()->create();
        $invoice = Invoice::factory()->create();

        $invoice->load("salesOrder");
        $user->personel_id = $invoice->salesOrder->personel_id;
        $user->save();
        return [
            "type" => "App\\Notifications\\PaymentDueNotification",
            "notifiable_type" => "Modules\\Authentication\\Entities\\User",
            "notifiable_id" => $user->id,
            "data" => [
                "detail" => [
                    "notified_feature" => "Direct Sale",
                    "notification_text" => "sales order  No. 74 telah jatuh tempo pada 2023-10-28 09:00:56",
                    "mobile_link" => "/DetailProformaDirectOrderPage",
                    "desktop_link" => "/marketing-staff/sales-history",
                    "data_id" => $invoice->id,
                    "as_marketing" => true,
                ],
                "user" => $user->toArray(),
                "notification_marketing_group_id" => NotificationMarketingGroup::factory(),
                "notified_feature" => "Direct Sale",
                "notification_text" => "sales order  No. 74 telah jatuh tempo pada 2023-10-28 09:00:56",
                "mobile_link" => "/DetailProformaDirectOrderPage",
                "desktop_link" => "/marketing-staff/sales-history",
                "as_marketing" => true, "status" => "paid,unpaid",
                "data_id" => $invoice->id,
                "personel_id" => $user->personel_id,
            ],
            "notification_marketing_group_id" => NotificationMarketingGroup::factory(),
            "notified_feature" => "Direct Sale",
            "notification_text" => "sales order  No. 74 telah jatuh tempo pada 2023-10-28 09:00:56",
            "notification_text_desc" => null,
            "date_count" => null,
            "mobile_link" => "/DetailProformaDirectOrderPage",
            "desktop_link" => "/marketing-staff/sales-history",
            "data_id" => $invoice->id,
            "as_marketing" => true,
            "status" => "paid,unpaid",
            "personel_id" => $user->personel_id,

        ];
    }

    public function paymentDueDirectSales()
    {
        return $this->state(function (array $attributes) {
            $user = User::factory()->create();
            $invoice = Invoice::factory()->create();

            $invoice->load("salesOrder");
            $user->personel_id = $invoice->salesOrder->personel_id;
            $user->save();

            return [
                "type" => "App\\Notifications\\PaymentDueNotification",
                "data_id" => $invoice->id,
                "status" => "paid,unpaid",
                "as_marketing" => true,
            ];
        });
    }
}
