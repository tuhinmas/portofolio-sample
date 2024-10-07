<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Modules\Address\Entities\Address;
use Modules\Address\Entities\District;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Entities\Region;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\KiosDealer\Entities\Dealer;
use Modules\KiosDealer\Entities\SubDealer;
use Modules\Personel\Entities\Personel;

/**
 *
 */
trait MarketingArea
{

    /* inactive marketing, handover to supervisor */
    public function personelHasArea($personel_id)
    {
        $status = false;
        $personel = Personel::findOrFail($personel_id);
        $status_fee_L1 = DB::table('status_fee')
            ->whereNull("deleted_at")
            ->where("name", "L1")
            ->first();

        $personel_mm = Personel::query()
            ->whereHas('position', function ($QQQ) {
                return $QQQ->where("is_mm", true);
            })
            ->where("status", "!=", "3")
            ->first();

        $personel_is_mm = Personel::query()
            ->whereHas('position', function ($QQQ) {
                return $QQQ->where("is_mm", true);
            })
            ->where("id", $personel_id)
            ->first();

        /* get region detail by personel include personel */
        $region_id = Region::query()
            ->with("personel")
            ->where("personel_id", $personel_id)
            ->get();

        /* get sub region detail to region include marketing each level */
        $sub_region_id = SubRegion::query()
            ->with([
                "region",
            ])
            ->where("personel_id", $personel_id)
            ->get();

        /* get district detail to region include marketing each level */
        $district_id = MarketingAreaDistrict::query()
            ->with([
                "subRegionWithRegion" => function ($QQQ) {
                    return $QQQ->with([
                        "region",
                    ]);
                },
            ])
            ->where("personel_id", $personel_id)
            ->get();

        /* applicator area */
        $applicator_area = MarketingAreaDistrict::query()
            ->where(function ($QQQ) use ($personel_id) {
                return $QQQ
                    ->where("applicator_id", $personel_id)
                    ->orWhere("personel_id", $personel_id);
            })
            ->get()
            ->each(function ($district) {
                $district->applicator_id = null;
                $district->save();
            });

        $replacement_personel = null;

        /**
         * non active marketing is MM it self,
         * all mdm will change spv to active
         * MM
         */
        if ($personel_is_mm) {
            $personel_mdm = Personel::query()
                ->whereHas('region')
                ->where("status", "!=", "3")
                ->get()
                ->each(function ($marketing) use ($personel_mm) {
                    $marketing->supervisor_id = $personel_mm?->id;
                    $marketing->save();
                });
        }

        /* not active marketing is MDM */
        if ($region_id->count() > 0) {
            $replacement_personel = $personel_mm?->id;

            /* update marketing each region with active MM */
            foreach ($region_id as $region) {
                $region->personel_id = $replacement_personel;
                $region->save();
            }

            /* get all marketing on sub region under nonactive personel */
            $region_id_list = $region_id->pluck("id")->toArray();
            $sub_region_personel = SubRegion::query()
                ->whereIn("region_id", $region_id_list)
                ->get()
                ->pluck("personel_id")
                ->toArray();

            $personel_update = Personel::query()
                ->whereIn("id", $sub_region_personel)
                ->where("id", "!=", $personel_id)
                ->where("id", "!=", $replacement_personel)
                ->get()
                ->each(function ($marketing) use ($replacement_personel) {
                    $marketing->supervisor_id = $replacement_personel;
                    $marketing->save();
                });

            $status = true;
        }

        if ($sub_region_id->count() > 0) {

            /* update marketing each sub region */
            foreach ($sub_region_id as $sub_region) {

                /* get MDM as replacement personel*/
                $replacement_personel = $sub_region->region->personel_id;

                /**
                 * if marketing sub region is MDM it self,
                 * MM will be set as replacement personel
                 */
                if ($replacement_personel == $personel->id) {
                    $replacement_personel = $personel_mm?->id;
                }
                $sub_region->personel_id = $replacement_personel;
                $sub_region->save();
            }

            /* get all personel on district */
            $sub_region_list = $sub_region_id->pluck("id")->toArray();
            $personel_on_district = MarketingAreaDistrict::query()
                ->whereIn("sub_region_id", $sub_region_list)
                ->get()
                ->pluck("personel_id")
                ->toArray();

            /* update personel distict to new supervisor */
            $personel_update = Personel::query()
                ->whereIn("id", $personel_on_district)
                ->where("id", "!=", $personel_id)
                ->where("id", "!=", $replacement_personel)
                ->get()
                ->each(function ($marketing) use ($replacement_personel) {
                    $marketing->supervisor_id = $replacement_personel;
                    $marketing->save();
                });

            $status = true;
        }

        if ($district_id->count() > 0) {
            $replacement_personel = null;

            /* update marketing each district */
            foreach ($district_id as $district) {

                /* get RMC */
                $replacement_personel = $district->subRegionWithRegion->personel_id;

                /**
                 * if marketing on district is RMC it self,
                 * MDM will be set as replacement personel
                 */
                if ($replacement_personel == $personel->id) {

                    /* get MDM */
                    $replacement_personel = $district->subRegionWithRegion->region->personel_id;

                    /**
                     * if marketing on district is MDM it self,
                     * MM will be set as replacement personel
                     */
                    if ($replacement_personel == $personel->id) {
                        $replacement_personel = $personel_mm?->id;
                    }
                }

                $district->personel_id = $replacement_personel;
                $district->save();
            }

            $status = true;
        }

        /**
         * Retailer take over
         */
        Dealer::query()
            ->where("personel_id", $personel_id)
            ->get()
            ->each(function ($dealer) use ($replacement_personel, $status_fee_L1) {
                unset($dealer->id);
                $dealer->personel_id = $replacement_personel;
                $dealer->status_fee = $status_fee_L1?->id;
                $dealer->save();
            });

        /* update marketing in sub dealer to supervisor */
        SubDealer::query()
            ->where("personel_id", $personel_id)
            ->get()
            ->each(function ($sub_dealer) use ($replacement_personel, $status_fee_L1) {
                $sub_dealer->personel_id = $replacement_personel;
                $sub_dealer->status_fee = $status_fee_L1?->id;
                $sub_dealer->save();
            });

        return $status;
    }

    /**
     * check dealer on which area
     *
     * @param [type] $dealer_id
     * @return void
     */
    public function marketingSubAndRegionBaseDealerAddress($dealer_id, $district_id)
    {
        $address = Address::where("parent_id", $dealer_id)->where("type", "dealer")->first()->district_id;
        $district = MarketingAreaDistrict::query()
            ->with([
                "subRegionWithRegion" => function ($QQQ) {
                    return $QQQ->with([
                        "personel",
                        "region" => function ($QQQ) {
                            return $QQQ->with([
                                "personel",
                            ]);
                        },
                    ]);
                },
            ])
            ->where("district_id", $district_id)
            ->first();

        return [
            "sub_region_personel" => $district->subRegionWithRegion->personel->id,
            "region_personel" => $district->subRegionWithRegion->region->personel->id,
        ];
    }

    public function districtListMarketing($personel_id)
    {
        $districts_list = [];
        $districts = DB::table('marketing_area_districts')
            ->whereNull("deleted_at")
            ->where("personel_id", $personel_id)
            ->get()
            ->pluck("district_id");

        $sub_regions = SubRegion::query()
            ->where("personel_id", $personel_id)
            ->with("district")
            ->get();

        $regions = Region::query()
            ->where("personel_id", $personel_id)
            ->with("subRegion")
            ->get();

        /**
         * push district to districts list if personel has district
         */
        if ($districts) {
            foreach ($districts as $district) {
                array_push($districts_list, $district);
            }
        }

        /**
         * if personel as RMC (or above but on sub region)
         * push districts on under sub region
         * to districts list
         */
        if ($sub_regions) {
            foreach ($sub_regions as $sub) {
                if ($sub->district) {
                    foreach ($sub->district as $district) {
                        array_push($districts_list, $district->district_id);
                    }
                }
            }
        }

        /**
         * if personel as MDM (or above but on region)
         * push districts on under sub region
         * to districts list
         */
        if ($regions) {
            foreach ($regions as $region) {
                if ($region->subRegion) {
                    foreach ($region->subRegion as $sub) {
                        if ($sub->district) {
                            foreach ($sub->district as $district) {
                                array_push($districts_list, $district->district_id);
                            }
                        }
                    }
                }
            }
        }

        $districts_list = collect($districts_list)->unique()->toArray();
        return $districts_list;
    }

    public function districtListAsMarketing($personel_id)
    {
        return DB::table('marketing_area_districts')
            ->whereNull("deleted_at")
            ->where("personel_id", $personel_id)
            ->get()
            ->pluck("district_id");
    }

    public function districtListMarketingList($personel_id)
    {
        $districts_list = [];
        $districts = DB::table('marketing_area_districts')
            ->whereNull("deleted_at")
            ->whereIn("personel_id", $personel_id)
            ->get()
            ->pluck("id");

        $sub_regions = SubRegion::query()
            ->whereIn("personel_id", $personel_id)
            ->with("district")
            ->get();

        $regions = Region::query()
            ->whereIn("personel_id", $personel_id)
            ->with("subRegion")
            ->get();

        /**
         * push district to districts list if personel has district
         */
        if ($districts) {
            foreach ($districts as $district) {
                array_push($districts_list, $district);
            }
        }

        /**
         * if personel as RMC (or above but on sub region)
         * push districts on under sub region
         * to districts list
         */
        if ($sub_regions) {
            foreach ($sub_regions as $sub) {
                if ($sub->district) {
                    foreach ($sub->district as $district) {
                        array_push($districts_list, $district->id);
                    }
                }
            }
        }

        /**
         * if personel as MDM (or above but on region)
         * push districts on under sub region
         * to districts list
         */
        if ($regions) {
            foreach ($regions as $region) {
                if ($region->subRegion) {
                    foreach ($region->subRegion as $sub) {
                        if ($sub->district) {
                            foreach ($sub->district as $district) {
                                array_push($districts_list, $district->id);
                            }
                        }
                    }
                }
            }
        }

        $districts_list = collect($districts_list)->unique()->toArray();
        return $districts_list;
    }

    /**
     * district list by area
     *
     * @param [type] $area_id == sub region id or region id
     * @return void
     */
    public function districtListByAreaId($area_id)
    {
        $districts_list = [];

        $sub_regions = SubRegion::query()
            ->where("id", $area_id)
            ->with("district")
            ->get();

        $regions = Region::query()
            ->where("id", $area_id)
            ->with("subRegion")
            ->get();

        /**
         * if area is sub region push districts
         * on under sub region
         * to districts list
         */
        if ($sub_regions) {
            foreach ($sub_regions as $sub) {
                if ($sub->district) {
                    foreach ($sub->district as $district) {
                        array_push($districts_list, $district->district_id);
                    }
                }
            }
        }

        /**
         * if area is region push districts
         * on under sub region
         * to districts list
         */
        if ($regions) {
            foreach ($regions as $region) {
                if ($region->subRegion) {
                    foreach ($region->subRegion as $sub) {
                        if ($sub->district) {
                            foreach ($sub->district as $district) {
                                array_push($districts_list, $district->district_id);
                            }
                        }
                    }
                }
            }
        }
        $districts_list = collect($districts_list)->unique()->toArray();
        return $districts_list;
    }

    public function districtListByAreaIdV2($area_id)
    {

        $sub_regions = DB::table("marketing_area_sub_regions")
            ->whereNull("deleted_at")
            ->where("id", $area_id)
            ->exists();

        $regions = DB::table("marketing_area_regions")
            ->whereNull("deleted_at")
            ->where("id", $area_id)
            ->exists();

        $districts_list = [];

        if ($sub_regions) {
            $districts_list = MarketingAreaDistrict::where('sub_region_id', $area_id)
                ->pluck('district_id')
                ->toArray();
        }

        if ($regions) {
            $districts_list = DB::table("marketing_area_districts")
                ->select("district_id")
                ->whereIn('sub_region_id', function ($query) use ($area_id) {
                    $query->select('id')
                        ->from('marketing_area_sub_regions')
                        ->where('region_id', $area_id);
                })
                ->whereNull("deleted_at")
                ->pluck('district_id')
                ->toArray();
        }

        return array_unique($districts_list);
    }

    /**
     * district list by area
     *
     * @param [type] $area_id == sub region id or region id
     * @return void
     */
    public function districtListByAreaIdArray($area_id)
    {
        $districts_list = [];

        $sub_regions = SubRegion::query()
            ->whereIn("id", $area_id)
            ->with("district")
            ->get();

        // dd($sub_regions);
        $regions = Region::query()
            ->whereIn("id", $area_id)
            ->with("subRegion")
            ->get();

        /**
         * if area is sub region push districts
         * on under sub region
         * to districts list
         */
        if ($sub_regions) {
            foreach ($sub_regions as $sub) {
                if ($sub->district) {
                    foreach ($sub->district as $district) {
                        array_push($districts_list, $district->district_id);
                    }
                }
            }
        }

        /**
         * if area is region push districts
         * on under sub region
         * to districts list
         */
        if ($regions) {
            foreach ($regions as $region) {
                if ($region->subRegion) {
                    foreach ($region->subRegion as $sub) {
                        if ($sub->district) {
                            foreach ($sub->district as $district) {
                                array_push($districts_list, $district->district_id);
                            }
                        }
                    }
                }
            }
        }
        $districts_list = collect($districts_list)->unique()->toArray();

        return $districts_list;
    }

    /**
     * district id list by area
     *
     * @param [type] $area_id == sub region id or region id
     * @return void
     */
    public function districtListIdByAreaId($area_id)
    {
        $districts_list = [];

        $sub_regions = SubRegion::query()
            ->where("id", $area_id)
            ->with("district")
            ->get();

        $regions = Region::query()
            ->where("id", $area_id)
            ->with("subRegion")
            ->get();

        /**
         * if area is sub region push districts
         * on under sub region
         * to districts list
         */
        if ($sub_regions) {
            foreach ($sub_regions as $sub) {
                if ($sub->district) {
                    foreach ($sub->district as $district) {
                        array_push($districts_list, $district->id);
                    }
                }
            }
        }

        /**
         * if area is region push districts
         * on under sub region
         * to districts list
         */
        if ($regions) {
            foreach ($regions as $region) {
                if ($region->subRegion) {
                    foreach ($region->subRegion as $sub) {
                        if ($sub->district) {
                            foreach ($sub->district as $district) {
                                array_push($districts_list, $district->id);
                            }
                        }
                    }
                }
            }
        }
        $districts_list = collect($districts_list)->unique()->toArray();
        return $districts_list;
    }

    /**
     * district id list by area
     *
     * @param [type] $area_id == sub region id or region id
     * @return void
     */
    public function marketingListByAreaId($area_id)
    {
        $marketing_list = [];

        $sub_regions = SubRegion::query()
            ->where("id", $area_id)
            ->with("district")
            ->get();

        $regions = Region::query()
            ->where("id", $area_id)
            ->with("subRegion")
            ->get();

        /**
         * if area is sub region push districts
         * on under sub region
         * to districts list
         */
        if ($sub_regions) {
            foreach ($sub_regions as $sub) {
                array_push($marketing_list, $sub->personel_id);
                if ($sub->district) {
                    foreach ($sub->district as $district) {
                        array_push($marketing_list, $district->personel_id);
                    }
                }
            }
        }

        /**
         * if area is region push districts
         * on under sub region
         * to districts list
         */
        if ($regions) {
            foreach ($regions as $region) {
                array_push($marketing_list, $region->personel_id);
                if ($region->subRegion) {
                    foreach ($region->subRegion as $sub) {
                        array_push($marketing_list, $sub->personel_id);
                        if ($sub->district) {
                            foreach ($sub->district as $district) {
                                array_push($marketing_list, $district->personel_id);
                            }
                        }
                    }
                }
            }
        }
        $marketing_list = collect($marketing_list)->unique()->toArray();
        return $marketing_list;
    }

    /* marketing list area id */
    public function marketingListByAreaListId($area_list_id): array
    {
        $marketing_list = [];

        $sub_regions = SubRegion::query()
            ->whereIn("id", $area_list_id)
            ->with("district")
            ->get();

        $regions = Region::query()
            ->whereIn("id", $area_list_id)
            ->with("subRegion")
            ->get();

        /**
         * if area is sub region push districts
         * on under sub region
         * to districts list
         */
        if ($sub_regions) {
            foreach ($sub_regions as $sub) {
                array_push($marketing_list, $sub->personel_id);
                if ($sub->district) {
                    foreach ($sub->district as $district) {
                        array_push($marketing_list, $district->personel_id);
                    }
                }
            }
        }

        /**
         * if area is region push districts
         * on under sub region
         * to districts list
         */
        if ($regions) {
            foreach ($regions as $region) {
                array_push($marketing_list, $region->personel_id);
                if ($region->subRegion) {
                    foreach ($region->subRegion as $sub) {
                        array_push($marketing_list, $sub->personel_id);
                        if ($sub->district) {
                            foreach ($sub->district as $district) {
                                array_push($marketing_list, $district->personel_id);
                            }
                        }
                    }
                }
            }
        }
        $marketing_list = collect($marketing_list)->unique()->toArray();
        return $marketing_list;
    }

    /**
     * district id list by area
     *
     * @param [type] $area_id == sub region id or region id
     * @return void
     */
    public function marketingListOnPersonelBranch($area_id)
    {
        $marketing_list = [];

        $sub_regions = SubRegion::query()
            ->whereIn("id", $area_id)
            ->with("district")
            ->get();

        $regions = Region::query()
            ->whereIn("id", $area_id)
            ->with("subRegion")
            ->get();

        /**
         * if area is sub region push districts
         * on under sub region
         * to districts list
         */
        if ($sub_regions) {
            foreach ($sub_regions as $sub) {
                array_push($marketing_list, $sub->personel_id);
                if ($sub->district) {
                    foreach ($sub->district as $district) {
                        array_push($marketing_list, $district->personel_id);
                    }
                }
            }
        }

        /**
         * if area is region push districts
         * on under sub region
         * to districts list
         */
        if ($regions) {
            foreach ($regions as $region) {
                array_push($marketing_list, $region->personel_id);
                if ($region->subRegion) {
                    foreach ($region->subRegion as $sub) {
                        array_push($marketing_list, $sub->personel_id);
                        if ($sub->district) {
                            foreach ($sub->district as $district) {
                                array_push($marketing_list, $district->personel_id);
                            }
                        }
                    }
                }
            }
        }
        $marketing_list = collect($marketing_list)->unique()->toArray();
        return $marketing_list;
    }

    /**
     * district id list by area
     *
     * @param [type] $area_id == sub region id or region id
     * @return void
     */
    public function districtListByDistrictId($area_id)
    {
        $districts_list = [];

        $district = MarketingAreaDistrict::query()
            ->where("district_id", $area_id)
            ->get();

        if ($district) {
            foreach ($district as $district) {
                array_push($districts_list, $district->district_id);
            }
        }

        $districts_list = collect($districts_list)->unique()->toArray();
        return $districts_list;
    }

    public function districtListById($area_id)
    {
        $districts_list = [];

        $district = MarketingAreaDistrict::query()
            ->where("id", $area_id)
            ->get();

        if ($district) {
            foreach ($district as $district) {
                array_push($districts_list, $district->district_id);
            }
        }

        $districts_list = collect($districts_list)->unique()->toArray();
        return $districts_list;
    }

    /**
     * get dealer id list according area id
     *
     * @param [type] $area_id
     * @return void
     */
    public function dealerListByArea($area_id)
    {
        $dealers_id = [];

        $sub_regions = SubRegion::query()
            ->where("id", $area_id)
            ->first();

        $regions = Region::query()
            ->where("id", $area_id)
            ->first();

        /**
         * area is sub region
         */
        if ($sub_regions) {
            $dealers = Dealer::query()
                ->whereHas("subRegionDealerDeepRelation", function ($QQQ) use ($area_id) {
                    return $QQQ->where("marketing_area_sub_regions.id", $area_id);
                })
                ->get();

            if ($dealers->count() > 0) {
                $dealers_id = $dealers->pluck("id")->toArray();
            }
        }

        /**
         * area is region
         */
        if ($regions) {
            $dealers = Dealer::query()
                ->whereHas("regionDealerDeepRelation", function ($QQQ) use ($area_id) {
                    return $QQQ->where("marketing_area_regions.id", $area_id);
                })
                ->get();

            if ($dealers->count() > 0) {
                $dealers_id = $dealers->pluck("id")->toArray();
            }
        }

        return $dealers_id;
    }

    /**
     * update marketing area with spv
     * if marketing change to
     * inactive
     */
    public function updateMarketingAreaToSupervisor($replaced_marketing, $replacement_marketing)
    {
        /* area marketing update */
        MarketingAreaDistrict::query()
            ->where("personel_id", $replaced_marketing)
            ->get()
            ->each(function ($district) use ($replacement_marketing) {
                $district->personel_id = $replacement_marketing;
                $district->save();
            });

        /* sub region update */
        SubRegion::query()
            ->where("personel_id", $replaced_marketing)
            ->get()
            ->each(function ($sub_region) use ($replacement_marketing) {
                $sub_region->personel_id = $replacement_marketing;
                $sub_region->save();
            });

        /* region update */
        Region::query()
            ->where("personel_id", $replaced_marketing)
            ->get()
            ->each(function ($region) use ($replacement_marketing) {
                $region->personel_id = $replacement_marketing;
                $region->save();
            });
    }
}
