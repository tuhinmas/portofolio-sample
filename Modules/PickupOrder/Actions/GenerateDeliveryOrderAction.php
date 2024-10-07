<?php

namespace Modules\PickupOrder\Actions;

use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\ReceiptDetail;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\DistributionChannel\Entities\DeliveryOrderNumber;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\DistributionChannel\Entities\DispatchOrderDetail;
use Modules\Organisation\Entities\Organisation;
use Modules\Personel\Entities\Personel;
use Modules\PromotionGood\Entities\DispatchPromotion;
use Modules\PromotionGood\Entities\DispatchPromotionDetail;

class GenerateDeliveryOrderAction
{
    public function __invoke(string $dispatch_order_id, bool $is_promotion = false, $delivery_date = null)
    {
        $month_conversion = [
            "01" => "I",
            "02" => "II",
            "03" => "III",
            "04" => "IV",
            "05" => "V",
            "06" => "VI",
            "07" => "VII",
            "08" => "VIII",
            "09" => "IX",
            "10" => "X",
            "11" => "XI",
            "12" => "XII",
        ];

        $attributes = [];
        if ($is_promotion) {
            $last_order_number = DB::table('delivery_orders')
                ->whereNull("deleted_at")
                ->whereYear("created_at", now())
                ->orderBy("order_number_promotion", "desc")
                ->first();

            if (!$last_order_number) {
                $order_number = (object) ["order_number_promotion" => 0];
                $last_order_number = $order_number;
            }

            $attributes["order_number_promotion"] = $last_order_number->order_number_promotion + 1;
            $delivery_number = now()->format("Y") . "/SJL-" . $month_conversion[now()->format("m")] . "/" . str_pad($last_order_number->order_number_promotion + 1, 5, 0, STR_PAD_LEFT);
            $attributes["delivery_order_number"] = $delivery_number;
            $attributes["dispatch_promotion_id"] = $dispatch_order_id;
        } else {
            $dispatch = DispatchOrder::query()
                ->with("invoice.salesOrder")
                ->findOrFail($dispatch_order_id);

            $dealer = DB::table('dealers')->where("id", $dispatch->invoice->salesOrder->store_id)->first();
            $attributes["marketing_id"] = $dealer?->personel_id;
            $attributes["dealer_id"] = $dealer?->id;
            $last_order_number = DB::table('delivery_orders')
                ->whereNull("deleted_at")
                ->whereYear("created_at", now())
                ->orderBy("order_number", "desc")
                ->lockForUpdate()
                ->first();

            if (!$last_order_number) {
                $order_number = (object) ["order_number" => 0];
                $last_order_number = $order_number;
            }
            $attributes["order_number"] = $last_order_number->order_number + 1;
            $delivery_number = now()->format("Y") . "/SJ-" . $month_conversion[now()->format("m")] . "/" . str_pad($last_order_number->order_number + 1, 5, 0, STR_PAD_LEFT);
            $attributes["delivery_order_number"] = $delivery_number;
            $attributes["dispatch_order_id"] = $dispatch_order_id;
        }

        $om = Personel::query()
            ->whereHas("position", function ($QQQ) {
                return $QQQ->where("name", "Operational Manager");
            })
            ->first();

        /* get receipt template */
        $receipt = DB::table('proforma_receipts')->whereNull("deleted_at")->where("receipt_for", "4")->orderBy("created_at", "desc")->first();
        $attributes["receipt_id"] = $receipt ? $receipt->id : null;
        $attributes["confirmed_by"] = $om ? $om->id : null;
        $attributes["created_by"] = auth()->user() ? auth()->user()?->personel_id : null;
        $organisation = $this->createReceiptDetail();
        $attributes["organisation_id"] = $organisation ? $organisation["organisation"]->id : null;
        $attributes["operational_manager_id"] = $om ? $om->id : null;
        $attributes["receipt_detail_id"] = $organisation["receipt_detail"]->id;
        $attributes["image_header_link"] = "https://javamas-bucket.s3.ap-southeast-1.amazonaws.com/public/nota/asset+template+pdf/Shipping.png";
        $attributes["image_footer_link"] = "https://javamas-bucket.s3.ap-southeast-1.amazonaws.com/public/nota/asset+template+pdf/Title.png";
        $attributes["date_delivery"] = $delivery_date ?? now();

        if ($is_promotion) {
            $delivery_order = DeliveryOrder::firstOrCreate([
                "dispatch_promotion_id" => $dispatch_order_id,
                "is_promotion" => 1,
                "status" => "send",
            ], $attributes);

            DispatchPromotionDetail::where("dispatch_promotion_id", $dispatch_order_id)->update([
                "quantity_packet_to_send" => DB::raw('planned_package_to_send'),
                "package_weight" => DB::raw('planned_package_weight'),
                "quantity_unit" => DB::raw('planned_quantity_unit'),
            ]);
        } else {
            $delivery_order = DeliveryOrder::firstOrCreate([
                "dispatch_order_id" => $dispatch_order_id,
            ], $attributes);

            DispatchOrderDetail::where("id_dispatch_order", $dispatch_order_id)->update([
                "quantity_packet_to_send" => DB::raw('planned_package_to_send'),
                "package_weight" => DB::raw('planned_package_weight'),
                "quantity_unit" => DB::raw('planned_quantity_unit'),
            ]);
        }

        if ($delivery_order->wasRecentlyCreated) {
            DeliveryOrderNumber::create([
                "dispatch_order_id" => $delivery_order->dispatch_order_id,
                "dispatch_promotion_id" => $delivery_order->dispatch_promotion_id,
                "delivery_order_id" => $delivery_order->id,
                "delivery_order_number" => $delivery_order->delivery_order_number,
            ]);
        }

    }

    public function createReceiptDetail()
    {
        $text_message = [
            "note_aggrement" => "Segala kerusakan dan kehilangan pada saat pengiriman menjadi tanggung jawab Armada / Supir setelah diterima dengan bukti tandatangan dibawah.
            Dengan ditandatanganinya surat jalan ini, maka surat jalan ini dipakai sekaligus sebagai tanda terima barang.",
            "note_checked" => "Barang - barang tersebut diatas sudah dicek, dihitung, dan telah diterima dengan kondisi yang baik dan benar oleh :",
        ];

        $organisation = Organisation::query()
            ->with("address")
            ->whereNull("deleted_at")
            ->where("name", "Javamas Agrophos")
            ->first();

        $organisation_address = collect($organisation->address)->where("type", "kantor")->first();
        if (!$organisation_address) {
            $organisation_address = "alamat organisasi kosong";
        } else {
            $organisation_address = $organisation_address->detail_address;
        }

        $receipt_detail = ReceiptDetail::create([
            "siup" => $organisation->siup,
            "npwp" => $organisation->npwp,
            "tdp" => $organisation->tdp,
            "ho" => $organisation->ho,
            "company_name" => $organisation->name,
            "company_address" => $organisation_address,
            "company_telephone" => $organisation->telephone,
            "company_hp" => $organisation->hp,
            "company_email" => $organisation->email,
            "note" => json_encode($text_message),
        ]);

        return [
            "organisation" => $organisation,
            "receipt_detail" => $receipt_detail,
        ];
    }
}
