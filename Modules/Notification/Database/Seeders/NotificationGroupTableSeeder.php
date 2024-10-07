<?php

namespace Modules\Notification\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Notification\Entities\NotificationGroup;

class NotificationGroupTableSeeder extends Seeder
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
                "role" => "Support"
            ],
            [
                "menu" => "Indirect Sales",
                "role" => "Support"
            ],
            [
                "menu" => "Event",
                "role" => "Support"
            ],
            [
                "menu" => "Agenda Marketing",
                "role" => "Support"
            ],
            [
                "menu" => "Dealer",
                "role" => "Support"
            ],
            [
                "menu" => "Sub Dealer",
                "role" => "Support"
            ],
            [
                "menu" => "Kontes Toko",
                "role" => "Support"
            ],
            [
                "menu" => "Kios",
                "role" => "Support"
            ],
            [
                "menu" => "Forecast",
                "role" => "Support"
            ],
            [
                "menu" => "Kalender Tanam",
                "role" => "Support"
            ],
            [
                "menu" => "Barang Promosi",
                "role" => "Support"
            ],
            [
                "menu" => "Penerimaan Barang",
                "role" => "Support"
            ],
            [
                "menu" => "Surat Jalan",
                "role" => "Support"
            ],
        ];

        foreach ($notification_group as $notif) {
            NotificationGroup::updateOrCreate($notif);
        }
    }
}
