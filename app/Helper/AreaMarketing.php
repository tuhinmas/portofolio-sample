<?php

use Illuminate\Support\Facades\DB;
use Modules\Personel\Entities\Personel;
use Modules\PersonelBranch\Entities\PersonelBranch;

if (!function_exists("positions_for_districts")) {
    function positions_for_districts()
    {
        return [
            'Regional Marketing Coordinator (RMC)',
            'Marketing District Manager (MDM)',
            'Regional Marketing (RM)',
            'Marketing Manager (MM)',
            'Assistant MDM',
        ];
    }
}

if (!function_exists("positions_for_sub_region")) {
    function positions_for_sub_region()
    {
        return [
            'Regional Marketing Coordinator (RMC)',
            'Marketing District Manager (MDM)',
            'Marketing Manager (MM)',
            'Assistant MDM',
        ];
    }
}

if (!function_exists("positions_for_region")) {
    function positions_for_region()
    {
        return [
            'Marketing District Manager (MDM)',
            'Marketing Manager (MM)',
        ];
    }
}

if (!function_exists("personel_branch")) {
    function personel_branch($personel_id = false)
    {
        $personel = Personel::find($personel_id);
        $regionBranch = PersonelBranch::where("personel_id", ($personel ? $personel_id : auth()->user()->personel_id))->get()->pluck("region_id")->toArray();
        $regionId = "'" . implode("','", $regionBranch) . "'";

        if ($regionId) {
            $results = DB::select(DB::raw("
                SELECT DISTINCT personel_id
                FROM (
                    -- Subregions and their marketing_area_districs
                    SELECT marketing_area_sub_regions.personel_id
                    FROM marketing_area_sub_regions
                    WHERE marketing_area_sub_regions.region_id IN ($regionId)
                    AND marketing_area_sub_regions.deleted_at is null
                    
                    UNION
                    
                    SELECT marketing_area_districts.personel_id
                    FROM marketing_area_sub_regions
                    JOIN marketing_area_districts ON marketing_area_districts.sub_region_id = marketing_area_sub_regions.id and marketing_area_districts.deleted_at is null
                    WHERE marketing_area_sub_regions.region_id IN ($regionId)
                    AND marketing_area_sub_regions.deleted_at is null
                    
                    UNION
                    
                    -- Regions and their subregions
                    SELECT marketing_area_regions.personel_id
                    FROM marketing_area_regions
                    WHERE marketing_area_regions.id IN ($regionId)
                    AND marketing_area_regions.deleted_at is null
                    
                    UNION
                    
                    SELECT marketing_area_sub_regions.personel_id
                    FROM marketing_area_regions
                    JOIN marketing_area_sub_regions ON marketing_area_sub_regions.region_id = marketing_area_regions.id AND marketing_area_regions.deleted_at is null
                    WHERE marketing_area_regions.id IN ($regionId)
                    AND marketing_area_regions.deleted_at is null 
                    
                    UNION
                    
                    SELECT marketing_area_districts.personel_id
                    FROM marketing_area_regions
                    JOIN marketing_area_sub_regions ON marketing_area_sub_regions.region_id = marketing_area_regions.id AND marketing_area_sub_regions.deleted_at is null
                    JOIN marketing_area_districts ON marketing_area_districts.sub_region_id = marketing_area_sub_regions.id AND marketing_area_districts.deleted_at is null
                    WHERE marketing_area_regions.id IN ($regionId)
                    AND marketing_area_regions.deleted_at is null
                    
                ) AS marketing_list
            "));

            $marketing_list = array_map(function($row) {
                return $row->personel_id;
            }, $results);
            
            return $marketing_list;
        }

        return false;

    }
}


