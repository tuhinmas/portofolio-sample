<?php

namespace Modules\KiosDealer\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Traits\ResponseHandlerV2;
use Illuminate\Routing\Controller;
use Modules\Address\Entities\City;
use Rap2hpoutre\FastExcel\FastExcel;
use Modules\Address\Entities\District;
use Modules\Address\Entities\Province;
use Modules\Personel\Entities\Personel;
use Modules\KiosDealer\Jobs\Import\ImportStoreJob;
use Modules\KiosDealer\Actions\Import\ImportStoresAction;

class ImportStoreController extends Controller
{
    use ResponseHandlerV2;

    public function __invoke(Request $request, ImportStoresAction $import_store_action)
    {
        $request->validate([
            'file' => [
                'required',
                'mimes:xls,xlsx,xlsm',
            ],

        ]);

        try {

            $stores = (new FastExcel)->import($request->file)
                ->map(fn($store) => (object) $store);

            /* import action */
            ImportStoreJob::dispatch($stores);

            // $import_store_action($stores);

            $unproccessed_data = $stores

            /* trim text */
                ->map(function ($store) {
                    $store = collect($store)
                        ->map(function ($value, $key) {
                            return trim($value);
                            return Str::of($value)->trim();
                        })
                        ->toArray();

                    $store["is_valid"] = true;
                    return (object) $store;
                })

                /* fixing case */
                ->map(function ($store) {
                    $store_new_case = collect($store)
                        ->except(["hp", "hp_cadangan", "latitude", "longitude", "gmaps_link", "hp_petani"])
                        ->map(function ($value, $key) {
                            return Str::title($value);
                        });

                    return (object) collect($store)->merge($store_new_case)->toArray();
                })

                /* validate address (province, city, district) */
                ->map(function ($store) {
                    $is_valid = (Province::query()
                            ->where("name", $store->provinsi)
                            ->first()
                        &&

                        City::query()
                            ->where("name", $store->kabupaten)
                            ->first()
                        &&

                        District::query()
                            ->where("name", $store->kecamatan)
                            ->first()
                    );

                    $store->is_valid = $is_valid;
                    return $store;
                })

                /* check the completeness of the attribute */
                ->map(function ($store) {
                    $count = collect($store)
                        ->except(["hp_cadangan", "latitude", "longitude", "gmaps_link"])
                        ->filter(function ($value, $key) {
                            return !$value;
                        })
                        ->count();

                    $is_valid = true;
                    if ($count > 0) {
                        $is_valid = false;
                    }

                    $store->is_valid = $is_valid;
                    return $store;
                })

                ->groupBy("marketing")

                /* validate marketing, if there exist */
                ->map(function ($store_per_marketing, $marketing) {
                    $personel = Personel::query()
                        ->where("name", $marketing)
                        ->first();

                    return $store_per_marketing->map(function ($store) use ($personel) {
                        if (!$personel) {
                            $store->is_valid = false;;
                        }
                        return $store;
                    });
                })

                /* validate store phone number must be unique for every store */
                ->map(function ($store_per_marketing, $marketing) {
                    $unique_store = collect($store_per_marketing)
                        ->unique(function ($store) {
                            return $store->nama_kios .
                            $store->nama_pemilik .
                            $store->alamat .
                            $store->provinsi .
                            $store->kabupaten .
                            $store->kecamatan;
                        });

                    $unique_hp = $unique_store->unique("hp");

                    if ($unique_hp->count() < $unique_store->count()) {
                        $fix_store = collect();
                        $invalid_hp = collect($unique_store)
                            ->groupBy("hp")
                            ->filter(function ($store_per_hp, $store) {
                                return collect($store_per_hp)->count() > 1;
                            })
                            ->flatten()
                            ->values();

                        $store_per_marketing = $store_per_marketing->map(function ($store) use ($invalid_hp) {
                            if (in_array($store->hp, $invalid_hp->pluck("hp")->unique()->toArray())) {
                                $store->is_valid = false;;
                            }

                            return $store;
                        });
                    }

                    $store_per_marketing["unique_store"] = $unique_store->count();
                    $store_per_marketing["unique_hp"] = $unique_hp->count();

                    return $store_per_marketing;
                })

                /* validate farmer phone number */
                ->map(function ($store_per_marketing, $marketing) {
                    return $store_per_marketing
                        ->groupBy("hp")

                        /* map as object relation (farmer to store) */
                        ->map(function ($store_per_hp) {
                            $detail["store"] = collect($store_per_hp)
                                ->map(function ($store) {
                                    return collect($store)->except(["nama_petani", "hp_petani", "alamat_petani"]);
                                })
                                ->first();

                            $detail["store"]["farmers"] = collect($store_per_hp)
                                ->map(function ($store) {
                                    return collect($store)->only(["nama_petani", "hp_petani", "alamat_petani"]);
                                });

                            return collect($detail)->first();
                        })
                        ->reject(function ($store_per_hp, $hp) {
                            return !$hp;
                        })
                        ->values()
                        ->map(function ($store) {
                            $invalid_hp = $store["farmers"]
                                ->groupBy("hp_petani")
                                ->filter(function ($farmer_per_hp) {
                                    return collect($farmer_per_hp)->count() > 1;
                                })
                                ->values()
                                ->flatten()
                                ->toArray();

                            $store["farmers"] = collect($store["farmers"])
                                ->reject(fn($farmer) => !$farmer)
                                ->map(function ($farmer) use ($invalid_hp) {
                                    $farmer["is_valid"] = true;

                                    if (in_array($farmer->toArray()["hp_petani"], $invalid_hp)) {
                                        $farmer["is_valid"] = false;
                                    }
                                    return $farmer;
                                });

                            return $store;;
                        });
                })

                /* fixing invalid data */
                ->map(function ($store_per_marketing, $marketing) {
                    return $store_per_marketing
                        ->filter(function ($store) {
                            return !$store["is_valid"];
                        })
                        ->values();
                })

                ->reject(function ($store_per_marketing, $marketing) {
                    return $store_per_marketing->count() < 1;
                });

            return $this->response("00", "success", [
                "unproccessed_data" => $unproccessed_data
            ]);
        } catch (\Throwable $th) {
            return $this->response("01", "failed", $th, 500);
        }
    }
}
