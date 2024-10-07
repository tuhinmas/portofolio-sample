<?php

namespace App\Traits;

use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Entities\Region;
use Modules\DataAcuan\Entities\SubRegion;
use Spatie\Permission\Traits\HasRoles;

trait SupervisorCheck
{
    use HasRoles;

    public function districtSupervisor($personel_id)
    {
        $districts = [];
        $city_list = [];
        $sub_region_list = [];
        $area_list = [];
        $role = [];
        $city = null;
        $district = null;
        $area = null;
        $regions = null;
        $user_roles = auth()->user()->getRoleNames();

        foreach ($user_roles as $val) {
            array_push($role, $val);
        }

        if (in_array("Regional Marketing (RM)", $role)) {
            $district = MarketingAreaDistrict::whereIn("personel_id", [$personel_id])
                ->get();

        } else if (in_array("Regional Marketing Coordinator (RMC)", $role)) {

            /*get sub region list first*/
            $city = SubRegion::query()
                ->with("city")
                ->where("personel_id", $personel_id)
                ->get();

            $area = $city;

            /*check if city on sub region has no marketing*/
            /*push city_id to array list if personel_id is null*/
            foreach ($city as $value) {
                foreach ($value->city as $val) {
                    if (!$val->personel_id) {
                        $city_id = $val->city_id;
                        array_push($city_list, $city_id);
                    }
                }
            }

            /*get all district with city_id and personel_id is null*/
            $district = MarketingAreaDistrict::query()
                ->whereIn("city_id", $city_list)
                ->whereNull("personel_id")
                ->get();

        } else if (in_array("Marketing District Manager (MDM)", $role) || in_array("Assistant MDM", $role)) {

            /*get region by personel_id*/
            $regions = Region::query()
                ->with("subRegion")
                ->where("personel_id", $personel_id)
                ->get();

            $area = $regions;
            /*check if sub region on region has no marketing*/
            /*push city_id to array list if personel_id is null*/
            foreach ($regions as $key => $region) {
                if ($region->subRegion) {
                    foreach ($region->subRegion as $sub_region) {
                        if (!$sub_region->personel_id) {
                            foreach ($sub_region->city as $city) {
                                if (!$city->personel_id) {
                                    $city_id = $city->city_id;
                                    array_push($city_list, $city_id);
                                }
                            }
                        }
                    }
                }
            }

            /*get all district with city_id and personel_id is null*/
            $district = MarketingAreaDistrict::query()
                ->whereIn("city_id", $city_list)
                ->whereNull("personel_id")
                ->get();

        } else if (in_array("Marketing Manager (MM)", $role)) {

            /*get region by personel_id*/
            $regions = Region::query()
                ->with("subRegion")
                ->where("personel_id", $personel_id)
                ->orWhereNull("personel_id")
                ->get();

            $area = $regions;

            /*check if sub region on region has no marketing*/
            /*push city_id to array list if personel_id is null*/
            foreach ($regions as $key => $region) {
                if ($region->subRegion) {
                    foreach ($region->subRegion as $sub_region) {
                        if (!$sub_region->personel_id) {
                            foreach ($sub_region->city as $city) {
                                if (!$city->personel_id) {
                                    $city_id = $city->city_id;
                                    array_push($city_list, $city_id);
                                }
                            }
                        }
                    }
                }
            }

            /*get all district with city_id and personel_id is null*/
            $district = MarketingAreaDistrict::query()
                ->whereIn("city_id", $city_list)
                ->whereNull("personel_id")
                ->get();
        }

        foreach ($district as $value) {
            array_push($districts, $value->district_id);
            array_push($area_list, $value);
        }

        $count_district = count($district);

        return compact("area_list", "districts", "count_district", "city_list", "role", "area");
    }

    public function districtSubordinateList($personel_id)
    {
        $districts = [];
        $city_list = [];
        $sub_region_list = [];
        $area_list = [];
        $role = [];
        $city = null;
        $district = null;
        $area = null;
        $regions = null;
        $user_roles = auth()->user()->getRoleNames();

        foreach ($user_roles as $val) {
            array_push($role, $val);
        }

        if (in_array("Regional Marketing (RM)", $role)) {
            $district = MarketingAreaDistrict::whereIn("personel_id", [$personel_id])
                ->get();

        } else if (in_array("Regional Marketing Coordinator (RMC)", $role)) {

            /*get sub region list first*/
            $city = SubRegion::query()
                ->with("city")
                ->where("personel_id", $personel_id)
                ->get();

            $area = $city;

            /*check if city on sub region has no marketing*/
            /*push city_id to array list if personel_id is null*/
            foreach ($city as $value) {
                foreach ($value->city as $val) {
                    $city_id = $val->city_id;
                    array_push($city_list, $city_id);
                }
            }

            /*get all district with city_id and personel_id is null*/
            $district = MarketingAreaDistrict::query()
                ->whereIn("city_id", $city_list)
                ->get();

        } else if (in_array("Marketing District Manager (MDM)", $role) || in_array("Assistant MDM", $role)) {

            /*get region by personel_id*/
            $regions = Region::query()
                ->with("subRegion")
                ->where("personel_id", $personel_id)
                ->get();

            $area = $regions;
            /*check if sub region on region has no marketing*/
            /*push city_id to array list if personel_id is null*/
            foreach ($regions as $key => $region) {
                if ($region->subRegion) {
                    foreach ($region->subRegion as $sub_region) {
                        foreach ($sub_region->city as $city) {
                            $city_id = $city->city_id;
                            array_push($city_list, $city_id);
                        }
                    }
                }
            }

            /*get all district with city_id and personel_id is null*/
            $district = MarketingAreaDistrict::query()
                ->whereIn("city_id", $city_list)
                ->get();

        } else {
            $district = MarketingAreaDistrict::query()
                ->get();
        }

        foreach ($district as $value) {
            array_push($districts, $value->district_id);
            array_push($area_list, $value);
        }

        $count_district = count($district);

        return compact("area_list", "districts", "count_district", "city_list", "role", "area");
    }

    public function districtListByArea($param)
    {
        $region = Region::where("id", $param)->first();
        $subRegion = SubRegion::where("id", $param)->first();
        $district = MarketingAreaDistrict::where("id", $param)->first();
        $district_list = [];
        if ($region) {
            $subRegions = SubRegion::with("district")->where("region_id", $region->id)->get();
            if ($subRegions) {
                foreach ($subRegions as $subRegion) {
                    foreach ($subRegion->district as $district) {
                        array_push($district_list, $district->district_id);
                    }
                }
            }
        } else if ($subRegion) {
            $districts = MarketingAreaDistrict::where("sub_region_id", $subRegion->id)->get();
            foreach ($districts as $district) {
                array_push($district_list, $district->district_id);
            }
        } else {
            array_push($district_list, $district->district_id);
        }

        return $district_list;
    }

    public function personelListByArea($param = null)
    {
        $region = Region::where("id", $param)->first();
        $subRegion = SubRegion::where("id", $param)->first();
        $district = MarketingAreaDistrict::where("id", $param)->first();
        $personel_list = [];
        if ($region) {
            $subRegions = SubRegion::with("district")->where("region_id", $region->id)->get();
            if ($subRegions) {
                foreach ($subRegions as $subRegion) {
                    foreach ($subRegion->district as $district) {
                        if ($district->personel_id) {
                            array_push($personel_list, $district->personel_id);
                            $personel_list = array_unique($personel_list);
                        }
                    }
                }
            }
        } else if ($subRegion) {
            $districts = MarketingAreaDistrict::where("sub_region_id", $subRegion->id)->get();
            foreach ($districts as $district) {
                if ($district->personel_id) {
                    array_push($personel_list, $district->personel_id);
                    $personel_list = array_unique($personel_list);
                }
            }
        } else if ($district) {
            if ($district->personel_id) {
                array_push($personel_list, $district->personel_id);
                $personel_list = array_unique($personel_list);
            }
        } else {
            $personels = MarketingAreaDistrict::get()->pluck("personel_id")->toArray();
            $personel_list = $personels;
        }

        return $personel_list;
    }

}
