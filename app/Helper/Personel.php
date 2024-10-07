<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Personel\Entities\Personel;

if (!function_exists("marketing_position_according_date")) {
    function marketing_position_according_date($personel, $date)
    {
        $position_according_date = DB::table('personel_position_histories')
            ->whereNull("deleted_at")
            ->where("personel_id", $personel->id)
            ->where("change_at", "<=", $date)
            ->orderBy("change_at", "desc")
            ->first();

        return $position_according_date ? $position_according_date->position_id : $personel->position_id;
    }
}

if (!function_exists("is_marketing_get_fee_according_resign_date")) {
    function is_marketing_get_fee_according_resign_date($resign_date, $sales_order): bool
    {
        if (empty($resign_date)) {
            return true;
        }

        /* last day as marketing still considered to get fee */
        if (confirmation_time($sales_order)->format("Y-m-d") <= $resign_date) {
            return true;
        }

        return false;
    }
}

if (!function_exists("personel_status_converter")) {
    function personel_status_converter($status)
    {
        $personel_status = "Aktif";
        switch ($status) {
            case '2':
                $personel_status = "Freeze";
                break;
            case '3':
                $personel_status = "Non Aktif";
                break;

            default:
                $personel_status = "Active";
                break;
        }

        return $personel_status;
    }
}

if (!function_exists("marketing_position_level")) {

    function marketing_position_level($position_name)
    {
        return collect([
            'Aplikator' => 0,
            'Regional Marketing (RM)' => 1,
            'Regional Marketing Coordinator (RMC)' => 2,
            'Assistant MDM' => 3,
            'Marketing District Manager (MDM)' => 4,
            'Marketing Manager (MM)' => 5,
        ])
            ->filter(function ($key, $value) use ($position_name) {
                return $value == $position_name;
            })
            ->values()
            ->first();
    }
}

if (!function_exists("support_position")) {

    function support_position()
    {
        return [
            "Support Supervisor",
            "Support Distributor",
            "Support Kegiatan",
            "Marketing Support",
        ];
    }
}

if (!function_exists("personel_join_days_get_fee")) {

    function personel_join_days_get_fee()
    {
        return 91;
    }
}

if (!function_exists("support_positions")) {

    function support_positions()
    {
        return [
            "Support Supervisor",
            "Support Distributor",
            "Support Kegiatan",
            "Marketing Support",
            "support",
        ];
    }
}

if (!function_exists("marketing_positions")) {

    function marketing_positions()
    {
        return [
            'Regional Marketing Coordinator (RMC)',
            'Marketing District Manager (MDM)',
            'Regional Marketing (RM)',
            'Marketing Manager (MM)',
            'Assistant MDM',
            "Aplikator"
        ];
    }
}

if (!function_exists("applicator_positions")) {

    function applicator_positions()
    {
        return [
            'Aplikator',
        ];
    }
}

if (!function_exists("personnel_is_applicator_position")) {

    function personnel_login_is_applicator_position()
    {
        if (in_array(Auth::user()->personel->position->name, applicator_positions())) {
            return true;
        }
        return false;
    }
}

if (!function_exists("supervisor_personel_active")) {
    function supervisor_personel_active($personelId)
    {
        $personel = Personel::selectRaw('id,supervisor_id,status')->whereColumn('supervisor_id', '!=', 'id')->find($personelId);
        if ($personel) {
            if ($personel->status == 1 || $personel->supervisor_id == null) {
                return $personel->id;
            }else {
                return supervisor_personel_active($personel->supervisor_id);
            }
        }
        
        return null;
    }
}

if (!function_exists("supervisor_personels")) {
    function supervisor_personels($personelId = false)
    {
        $personel_login_id = auth()->user()->personel_id;
        if ($personelId) {
            $personel_login_id = $personelId;
        }

        $personel = Personel::with("children.children.children.children")->findOrFail($personel_login_id);
        $personels_id[] = $personel->id;
        foreach ($personel->children as $level1) { //mdm
            $personels_id[] = $level1->id;
            if ($level1->children != []) {
                foreach ($level1->children as $level2) { //assistant mdm
                    $personels_id[] = $level2->id;
                    if ($level2->children != []) {
                        foreach ($level2->children as $level3) { //rmc
                            $personels_id[] = $level3->id;
                            if ($level3->children != []) {
                                foreach ($level3->children as $level4) { //rm
                                    $personels_id[] = $level4->id;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $personels_id;
    }
}


if (!function_exists("personel_with_child")) {
    function personel_with_child($personelId = false)
    {
        $personel = Personel::with("children.children.children.children")->find($personelId ?? auth()->user()->personel_id);
        $children = collect();
        collectChildren($personel, $children);

        return $children->pluck('id')->toArray();
    }

    function collectChildren($personel, &$children)
    {
        $children->push($personel);

        if ($personel->children()->count() > 0) {
            foreach ($personel->children as $child) {
                collectChildren($child, $children);
            }
        }
    }
}



