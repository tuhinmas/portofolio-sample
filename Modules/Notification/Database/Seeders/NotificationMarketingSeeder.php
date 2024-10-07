<?php

namespace Modules\Notification\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Modules\Notification\Entities\NotificationGroup;
use Modules\Notification\Entities\NotificationGroupDetail;

class NotificationGroupDetailTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $notifiction_group_details = [

            /* indirect sale */
            [
                "type" => "Indirect Sales",
                "notifiable_type" => "Modules\Authentication\Entities\User",
                "data" => json_encode([
                    "notif_type" => 1,
                    "condition" => "saat stok minus",
                ]),
                "notifiable_id" => "6ca5e37c-6683-4060-a47e-189edd408f0d",
                "read_at" => null,
                "mobile_link" => "/DetailIndirectPage",
                "notification_marketing_group_id" => "",
                "notified_feature" => "",
                "notification_text" => "",
                "notification_text" => "",
                "desktop_link" => "/marketing-support/indirect-sales/distributor-stock-control",
                "model" => "App\Models\Dealer",
            ],
            [
                "notification_group" => "Indirect Sales",
                "task_text" => "laporan CO perlu diproof",
                "condition" => json_encode([
                    "notif_type" => 2,
                    "condition" => "Laporan CO dilaporkan oleh supervisor",
                    "status" => ["submited"],
                    "type" => 2,
                ]),
                "task_count" => 0,
                "mobile_link" => "",
                "desktop_link" => "/marketing-support/indirect-sales/proofing-indirect-sales",
                "model" => "App\Models\SalesOrder",
            ],

            /* direct sale */
            [
                "notification_group" => "Direct Sales",
                "task_text" => "ada direct sales order masuk",
                "condition" => json_encode([
                    "notif_type" => 3,
                    "condition" => "Saat ada direct order dibuat oleh marketing",
                    "status" => ["submited"],
                    "type" => 1,
                ]),
                "task_count" => 0,
                "mobile_link" => "",
                "desktop_link" => "/marketing-support/reconfirm-order",
                "model" => "App\Models\SalesOrder",
            ],
            [
                "notification_group" => "Direct Sales",
                "task_text" => "peringatan Jatuh tempo pembayaran",
                "condition" => json_encode([
                    "notif_type" => 4,
                    "condition" => "Jika status pembayaran belum dilunasi dan 7 hari sebelum batas pelunasan",
                    "status" => ["paid", "unpaid"],
                    "type" => 1,
                ]),
                "task_count" => 0,
                "mobile_link" => "",
                "desktop_link" => "/marketing-support/sales-history-support",
                "model" => "App\Models\Invoice",
            ],

            /* dealer */
            [
                "notification_group" => "Dealer",
                "task_text" => "Dealer perlu ditinjau",
                "condition" => json_encode([
                    "notif_type" => 5,
                    "condition" => "setelah marketing mengajukan dealer baru atau mengajukan perubahan",
                    "status" => [
                        'submission of changes',
                        'filed',
                    ],
                ]),
                "task_count" => 0,
                "mobile_link" => "/DealerInfo",
                "desktop_link" => "/marketing-support/dealer/konfirmasi-dealer",
                "model" => "App\Models\DealerTemp",
            ],
            [
                "notification_group" => "Dealer",
                "task_text" => "Dealer perlu disetujui",
                "condition" => json_encode([
                    "notif_type" => 6,
                    "condition" => "setelah marketing mengajukan dealer baru atau mengajukan perubahan dan sudah dikonfirmasi oleh support",
                    "status" => ["wait approval"],
                ]),
                "task_count" => 0,
                "mobile_link" => "",
                "desktop_link" => "/marketing-support/dealer/agreement-dealer",
                "model" => "App\Models\DealerTemp",
            ],
            [
                "notification_group" => "Dealer",
                "task_text" => "kontrak distributor hampir habis",
                "condition" => json_encode([
                    "notif_type" => 7,
                    "condition" => "30 hari sebelum sebuah kontrak distributor habis, sampai kontrak baru dibuat atau 30 hari setelah habis tanggal kontrak",
                ]),
                "task_count" => 0,
                "mobile_link" => "",
                "desktop_link" => "/marketing-support/contract-distributor",
                "model" => "App\Models\Dealer",
            ],

            /* Sub Dealer */
            [
                "notification_group" => "Sub Dealer",
                "task_text" => "pengajuan baru dan perubahan data subdealer perlu ditinjau",
                "condition" => json_encode([
                    "notif_type" => 8,
                    "condition" => "setelah marketing meregistrasi subdealer",
                ]),
                "task_count" => 0,
                "mobile_link" => "",
                "desktop_link" => "/marketing-support/sub-dealer-confirmation",
                "model" => "App\Models\SubDealer",
            ],

            /* event */
            [
                "notification_group" => "Event",
                "task_text" => "Pengajuan event perlu disetujui",
                "condition" => json_encode([
                    "notif_type" => 9,
                    "condition" => "setelah event disetujui supervisor",
                    "status" => ["3"],
                ]),
                "task_count" => 0,
                "mobile_link" => "/ListEventSupervisorPage",
                "desktop_link" => "/marketing-support/submission-event/list-submission",
                "model" => "App\Models\Event",
            ],
            [
                "notification_group" => "Event",
                "task_text" => "Pengajuan pembatalan event",
                "condition" => json_encode([
                    "notif_type" => 10,
                    "condition" => "saat marketing mengajukan pembatalan event",
                    "status" => ["11"],
                ]),
                "task_count" => 0,
                "mobile_link" => "/ListEventSupervisorPage",
                "desktop_link" => "/marketing-support/submission-event/list-submission",
                "model" => "App\Models\Event",
            ],
            [
                "notification_group" => "Event",
                "task_text" => "Laporan event perlu disetujui",
                "condition" => json_encode([
                    "notif_type" => 11,
                    "condition" => "saat marketing melaporkan event",
                    "status" => ["8"],
                ]),
                "task_count" => 0,
                "mobile_link" => "/ListEventSupervisorPage",
                "desktop_link" => "/marketing-support/submission-event/list-submission",
                "model" => "App\Models\Event",
            ],

            /* contest */
            [
                "notification_group" => "Kontes Toko",
                "task_text" => "Kontrak kontes perlu disetujui",
                "condition" => json_encode([
                    "notif_type" => 12,
                    "condition" => "pengajuan kontrak kontes baru dari marketing",
                    "participant_status" => ["2"],
                ]),
                "task_count" => 0,
                "mobile_link" => "",
                "desktop_link" => "/marketing-support/contest-contract-confirmation",
                "model" => "App\Models\Contest",
            ],
            [
                "notification_group" => "Kontes Toko",
                "task_text" => "Kontes perlu disetujui",
                "condition" => json_encode([
                    "notif_type" => 13,
                    "condition" => "pengajuan jenis kontes baru",
                    "status" => ["2"],
                ]),
                "task_count" => 0,
                "mobile_link" => "",
                "desktop_link" => "/marketing-support/shop-contest-confirmation",
                "model" => "App\Models\Contest",
            ],
            [
                "notification_group" => "Kontes Toko",
                "task_text" => "Hadiah Kontes bisa diredeem",
                "condition" => json_encode([
                    "notif_type" => 14,
                    "condition" => "ada peserta kontes yang bisa melakukan redeem hadiah",
                    "participant_status" => ["4"],
                    "participation_status" => ["4"],
                    "status" => "today > contest end date",
                ]),
                "task_count" => 0,
                "mobile_link" => "",
                "desktop_link" => "/marketing-support/list-redeem-contest",
                "model" => "App\Models\Contest",
            ],

            /* Penerimaan Barang */
            [
                "notification_group" => "Penerimaan Barang",
                "task_text" => "Laporan ketidaksesuaian penerimaan barang",
                "condition" => json_encode([
                    "notif_type" => 15,
                    "condition" => "",
                    "status" => [
                        "broken",
                        "incorrect",
                    ],
                ]),
                "task_count" => 0,
                "mobile_link" => "",
                "desktop_link" => "/marketing-support/receiving-goods-history",
                "model" => "App\Models\ReceivingGoodDetail",
            ],

            /* Kios */
            [
                "notification_group" => "Kios",
                "task_text" => "pengajuan kios dan perubahan data kios perlu ditinjau",
                "condition" => json_encode([
                    "notif_type" => 16,
                    "condition" => "saat ada pengajuan kios baru atau perubahan",
                    "status" => [
                        'submission of changes',
                        'filed',
                    ],
                ]),
                "task_count" => 0,
                "mobile_link" => "/DetailStoreSubmissionPage",
                "desktop_link" => "/marketing-support/store/store-confirmation",
                "model" => "App\Models\Stores",
            ],
          
            /* Forecast */
            [
                "notification_group" => "Forecast",
                "task_text" => "forecast butuh ditinjau",
                "condition" => json_encode([
                    "notif_type" => 17,
                    "condition" => "terjadi saat akhir bulan",
                    "status" => ["submitted"],
                ]),
                "task_count" => 0,
                "mobile_link" => "/ListForecastSupervisor",
                "desktop_link" => "/marketing-support/forecast",
                "model" => "App\Models\Forecast",
            ],

            /* Surat Jalan */
            [
                "notification_group" => "Surat Jalan",
                "task_text" => "ada direct order yang belum tuntas pengirimannya",
                "condition" => json_encode([
                    "notif_type" => 18,
                    "condition" => "Direct Order",
                    "status" => ["confirmed"],
                ]),
                "task_count" => 0,
                "mobile_link" => "",
                "desktop_link" => "/marketing-support/atur-muatan",
                "model" => "Modules\SalesOrder\Entities\SalesOrder",
            ],
            [
                "notification_group" => "Surat Jalan",
                "task_text" => "ada barang promosi yang belum tuntas pengirimannya",
                "condition" => json_encode([
                    "notif_type" => 19,
                    "condition" => "Direct Order",
                    "status" => ["confirmed"],
                ]),
                "task_count" => 0,
                "mobile_link" => "",
                "desktop_link" => "/marketing-support/atur-muatan",
                "model" => "Modules\PromotionGood\Entities\PromotionGoodRequest",
            ],
            [
                "notification_group" => "Surat Jalan",
                "task_text" => "ada direct order yang diterima hari ini",
                "condition" => json_encode([
                    "notif_type" => 20,
                    "condition" => "Direct Order",
                    "status" => ["confirmed"],
                ]),
                "task_count" => 0,
                "mobile_link" => "",
                "desktop_link" => "/marketing-support/atur-muatan",
                "model" => "Modules\SalesOrder\Entities\SalesOrder",
            ],
            [
                "notification_group" => "Surat Jalan",
                "task_text" => "ada barang promosi yang diterima hari ini",
                "condition" => json_encode([
                    "notif_type" => 21,
                    "condition" => "Direct Order",
                    "status" => ["confirmed"],
                ]),
                "task_count" => 0,
                "mobile_link" => "",
                "desktop_link" => "/marketing-support/atur-muatan",
                "model" => "Modules\PromotionGood\Entities\PromotionGoodRequest",
            ],
        ];

        foreach ($notifiction_group_details as $notif) {
            $notification_group = NotificationGroup::where("menu", $notif["notification_group"])->first();
            $new_notif = collect($notif)->except(["notification_group"])->toArray();
            $new_notif["notification_group_id"] = $notification_group->id;
            NotificationGroupDetail::updateOrCreate($new_notif);
        }
    }
}
