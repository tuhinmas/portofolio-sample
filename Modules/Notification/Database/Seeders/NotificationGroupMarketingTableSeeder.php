<?php

namespace Modules\Notification\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Notification\Entities\NotificationGroup;
use Modules\Notification\Entities\NotificationMarketingGroup;

class NotificationGroupMarketingTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $notification_group = [
            [
                "menu" => "Direct Sales",
                "role" => "Marketing"
            ],
            [
                "menu" => "Indirect Sales",
                "role" => "Marketing"
            ],
            [
                "menu" => "Event",
                "role" => "Marketing"
            ],
            [
                "menu" => "Agenda Marketing",
                "role" => "Marketing"
            ],
            [
                "menu" => "Dealer",
                "role" => "Marketing"
            ],
            [
                "menu" => "Sub Dealer",
                "role" => "Marketing"
            ],
            [
                "menu" => "Kontes Toko",
                "role" => "Marketing"
            ],
            [
                "menu" => "Kios",
                "role" => "Marketing"
            ],
            [
                "menu" => "Forecast",
                "role" => "Marketing"
            ],
            [
                "menu" => "Kalender Tanam",
                "role" => "Marketing"
            ],
            [
                "menu" => "Penerimaan Barang",
                "role" => "Marketing"
            ],

            [
                "menu" => "Direct Sales",
                "role" => "Supervisor"
            ],
            [
                "menu" => "Indirect Sales",
                "role" => "Supervisor"
            ],
            [
                "menu" => "Event",
                "role" => "Supervisor"
            ],
            [
                "menu" => "Agenda Marketing",
                "role" => "Supervisor"
            ],
            [
                "menu" => "Dealer",
                "role" => "Supervisor"
            ],
            [
                "menu" => "Sub Dealer",
                "role" => "Supervisor"
            ],
            [
                "menu" => "Kontes Toko",
                "role" => "Supervisor"
            ],
            [
                "menu" => "Kios",
                "role" => "Supervisor"
            ],
            [
                "menu" => "Forecast",
                "role" => "Supervisor"
            ],
            [
                "menu" => "Kalender Tanam",
                "role" => "Supervisor"
            ],
            [
                "menu" => "Penerimaan Barang",
                "role" => "Supervisor"
            ],
        ];

        foreach ($notification_group as $notif) {
            NotificationMarketingGroup::updateOrCreate($notif);
        }
    }
}
