<?php

namespace App\Imports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Modules\DataAcuan\Entities\Product;
use Modules\ForeCast\Entities\ForeCast;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\Personel\Entities\Personel;

class ForecastImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        /* validate year */
        if ($row["1"] >= now()->format("Y") && $row["2"] <= 12 && $row["2"] >= 01 && $row["7"] >= 1) {
            
            /* marketing check */
            $row["2"] = Carbon::parse($row["1"] . "-" . $row["2"])->format("m");
            $personel = Personel::where("name", $row["0"])->first();

            /* dealer check */
            $dealer = null;
            if ($row["4"] == "dealer") {
                $dealer = Dealer::where("dealer_id", $row["3"])->first();
            }

            /* sub dealer check */
            $sub_dealer = null;

            if ($row["4"] == "sub_dealer") {
                $sub_dealer = SubDealer::where("sub_dealer_id", $row["3"])->first();
            }

            /* product check */
            $product = Product::query()
                ->with([
                    "price",
                ])
                ->where("name", $row["5"])
                ->where("size", $row["6"])
                ->whereHas("price")
                ->first();

            if (($dealer || $sub_dealer) && $product && $personel) {
                $forecast =  ForeCast::updateOrCreate([
                    "dealer_id" => $dealer ? $dealer->id : ($sub_dealer ? $sub_dealer->id : null),
                    "product_id" => $product?->id,
                    "personel_id" => $personel?->id,
                    "dealer_category" => $dealer ? "dealers" : ($sub_dealer ? "sub_dealers" : null),
                    "date" => $row["1"] . "-" . $row["2"] . "-" . now()->format("d 00:00:00"),
                ], [
                    "quantity" => $row["7"],
                    "price" => collect($product->price)->sortBy("price")->first()?->price,
                    "unit" => $product?->unit,
                    "nominal" => $row["7"] * collect($product->price)->sortBy("price")->first()?->price,
                ]);

                return $forecast;
            }

        }
    }
}
