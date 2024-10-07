<?php

namespace Modules\DistributionChannel\Database\factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\ReceiptDetail;
use Modules\DistributionChannel\Entities\DispatchOrder;
use Modules\Invoice\Entities\Invoice;
use Modules\KiosDealer\Entities\Dealer;
use Modules\Organisation\Entities\Organisation;
use Modules\Personel\Entities\Personel;
use Modules\PromotionGood\Entities\DispatchPromotionDetail;
use Modules\PromotionGood\Entities\PromotionGood;
use Modules\SalesOrder\Entities\SalesOrder;

class DeliveryOrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Modules\DistributionChannel\Entities\DeliveryOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $dispatch = DispatchOrder::factory()->create();
        $profornma = Invoice::find($dispatch->invoice_id);
        $sales_order = SalesOrder::query()
            ->where("model", "1")
            ->where("id", $profornma->sales_order_id)
            ->first();

        $support = Personel::query()
            ->inRandomOrder(1)
            ->whereHas("position", function ($QQQ) {
                return $QQQ->whereIn("name", support_position());
            })
            ->first();
        $receipt = DB::table('proforma_receipts')->whereNull("deleted_at")->where("receipt_for", "4")->orderBy("created_at", "desc")->first();

        $dealer = Dealer::findOrFail($sales_order->store_id);
        $receipt_detail = $this->createReceiptDetail();
        $last_order_number = DB::table('delivery_orders')
            ->whereNull("deleted_at")
            ->whereYear("created_at", now())
            ->orderBy("order_number", "desc")
            ->first();

        if (!$last_order_number) {
            $order_number = (object) ["order_number" => 0];
            $last_order_number = $order_number;
        }

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
        $delevery_number = Carbon::now()->format("Y") . "/SJ-" . $month_conversion[Carbon::now()->format("m")] . "/" . str_pad($last_order_number->order_number + 1, 5, 0, STR_PAD_LEFT);
        return [
            "date_delivery" => now()->format("Y-m-"),
            "dispatch_order_id" => $dispatch->id,
            "operational_manager_id" => $support?->id,
            "marketing_id" => $support?->id,
            "dealer_id" => $dealer->id,
            "organisation_id" => $receipt_detail["organisation"]->id,
            "receipt_detail_id" => $receipt_detail["receipt_detail"]->id,
            "delivery_order_number" => $delevery_number,
            "order_number" => $last_order_number->order_number + 1,
            "image_header_link" => $this->faker->url,
            "image_footer_link" => $this->faker->url,
            "receipt_id" => $receipt?->id,
            "confirmed_by" => $support?->id,
            "status" => "send",
        ];
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

    public function dispatchPromotion()
    {
        return $this->state(function (array $attributes) {
            $dispatch_detail = DispatchPromotionDetail::factory()->create();
            return [
                "dispatch_order_id" => null,
                "dispatch_promotion_id" => $dispatch_detail->dispatch_promotion_id,
            ];
        });
    }

    public function dispatchPromotionNonProduct()
    {
        return $this->state(function (array $attributes) {
            $dispatch_detail = DispatchPromotionDetail::factory()->create();

            PromotionGood::query()
                ->where("id", $dispatch_detail->promotion_good_id)
                ->update([
                    "product_id" => null,
                ]);

            return [
                "dispatch_order_id" => null,
                "dispatch_promotion_id" => $dispatch_detail->dispatch_promotion_id,
            ];
        });
    }
}
