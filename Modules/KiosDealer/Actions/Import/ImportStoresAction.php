<?php

namespace Modules\KiosDealer\Actions\Import;

use Illuminate\Support\Str;
use Modules\Address\Entities\City;
use Modules\Address\Entities\District;
use Modules\Address\Entities\Province;
use Modules\KiosDealer\Entities\CoreFarmer;
use Modules\KiosDealer\Entities\Store;
use Modules\Personel\Entities\Personel;

class ImportStoresAction
{
    /**
     * import stores with validation
     *
     * @param [collection] $stores
     * collection shold contain these attributes:
     * marketing
     * nama_kios
     * nama_pemilik
     * alamat
     * hp
     * hp_cadangan
     * provinsi
     * kabupaten
     * kecamatan
     * latitude
     * longitude
     * gmaps_link
     * nama_petani
     * hp_petani
     * alamat_petani
     * @return void
     */
    public function __invoke($stores)
    {
        return $stores

        /* trim value */
            ->map(function ($store) {
                $store = collect($store)
                    ->map(function ($value, $key) {
                        return trim($value);
                        return Str::of($value)->trim();
                    })
                    ->toArray();

                return (object) $store;
            })

            /* check the completeness of the attribute */
            ->reject(function ($store) {
                return collect($store)
                    ->except(["hp_cadangan", "latitude", "longitude", "gmaps_link"])
                    ->filter(function ($value, $key) {
                        return !$value;
                    })
                    ->count()
                > 0;
            })

            /* fixing hp and second hp */
            ->map(function ($store) {
                $is_hp_start_with_zero = Str::startsWith($store->hp, '0');
                $fixed_hp = Str::replaceFirst('0', '', $store->hp);
                $store->hp = $is_hp_start_with_zero ? $fixed_hp : $store->hp;

                $is_hp_start_with_zero = Str::startsWith($store->hp_cadangan, '0');
                $fixed_hp = Str::replaceFirst('0', '', $store->hp_cadangan);
                $store->hp_cadangan = $is_hp_start_with_zero ? $fixed_hp : $store->hp_cadangan;

                $is_hp_start_with_zero = Str::startsWith($store->hp_petani, '0');
                $fixed_hp = Str::replaceFirst('0', '', $store->hp_petani);
                $store->hp_petani = $is_hp_start_with_zero ? $fixed_hp : $store->hp_petani;

                return $store;
            })

            /* fixing latitude and longitude */
            ->map(function ($store) {
                $is_latitude_valid = (count(explode(".", $store->latitude)) <= 2 && !Str::contains($store->latitude, ','));
                $is_longitude_valid = (count(explode(".", $store->longitude)) <= 2 && !Str::contains($store->longitude, ','));

                $store->latitude = $is_latitude_valid ? $store->latitude : null;
                $store->longitude = $is_longitude_valid ? $store->longitude : null;
                return $store;
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

            ->sortBy("marketing")

            /* validate address (province, city, district) */
            ->reject(function ($store) {
                return !Province::query()
                    ->where("name", $store->provinsi)
                    ->first()
                ||

                !City::query()
                    ->where("name", $store->kabupaten)
                    ->first()
                ||

                !District::query()
                    ->where("name", $store->kecamatan)
                    ->first();
            })

            ->groupBy("marketing")

            /* validate marketing, if there exist */
            ->reject(function ($store_per_marketing, $marketing) {
                return !Personel::query()
                    ->where("name", $marketing)
                    ->first();
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

                $fix_store = $store_per_marketing;
                if ($unique_hp->count() < $unique_store->count()) {
                    $fix_store = collect();
                    collect($unique_store)
                        ->groupBy("hp")
                        ->reject(function ($store_per_hp, $store) {
                            return collect($store_per_hp)->count() > 1;
                        })
                        ->flatten()
                        ->values()
                        ->each(function ($store) use (&$fix_store, $store_per_marketing) {
                            $store_fix = $store_per_marketing
                                ->where("nama_kios", $store->nama_kios)
                                ->where("nama_pemilik", $store->nama_pemilik)
                                ->where("alamat", $store->alamat)
                                ->where("provinsi", $store->provinsi)
                                ->where("kabupaten", $store->kabupaten)
                                ->where("kecamatan", $store->kecamatan)
                                ->where("hp", $store->hp)
                                ->values();

                            $fix_store->push($store_fix);
                        });

                    $fix_store = $fix_store->flatten();

                }

                return $fix_store;
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
                    ->values()
                    ->map(function ($store) {
                        $store["farmers"] = $store["farmers"]
                            ->groupBy("hp_petani")
                            ->reject(function ($farmer_per_hp) {
                                return collect($farmer_per_hp)->count() > 1;
                            })
                            ->values()
                            ->flatten(1);

                        return $store;
                    });
            })

            /* upsert data stores */
            ->each(function ($store_per_marketing, $marketing) {
                $marketing = Personel::query()
                    ->where("name", $marketing)
                    ->first();

                if ($marketing) {
                    collect($store_per_marketing)
                        ->each(function ($store) use ($marketing) {
                            $store = (object) $store->toArray();
                            $province = Province::query()
                                ->where("name", $store->provinsi)
                                ->first();

                            $city = City::query()
                                ->where("name", $store->kabupaten)
                                ->first();

                            $district = District::query()
                                ->where("name", $store->kecamatan)
                                ->first();

                            $store_new = Store::updateOrCreate([
                                "personel_id" => $marketing->id,
                                "name" => $store->nama_kios,
                                "owner_name" => $store->nama_pemilik,
                                "address" => $store->alamat,
                                "gmaps_link" => $store->gmaps_link,
                                "province_id" => $province->id,
                                "city_id" => $city->id,
                                "district_id" => $district->id,
                            ], [
                                "telephone" => $store->hp,
                                "second_telephone" => $store->hp_cadangan,
                                "status" => "accepted",
                                "status_color" => "000000",
                                "note" => "kios dari import",
                                "latitude" => $store->latitude,
                                "longitude" => $store->longitude,
                                "gmaps_link" => $store->gmaps_link,
                            ]);

                            /* upsert data farmers */
                            collect($store->farmers)->each(function ($farmer) use ($store_new) {
                                CoreFarmer::updateOrCreate([
                                    "store_id" => $store_new->id,
                                    "name" => $farmer["nama_petani"],
                                    "telephone" => $farmer["hp_petani"],
                                ], [
                                    "address" => $farmer["alamat_petani"],
                                ]);
                            });
                        });
                }
            });
    }
}
