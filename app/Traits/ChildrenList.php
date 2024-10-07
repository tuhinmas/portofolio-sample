<?php

namespace App\Traits;

use App\Traits\SelfReferenceTrait;
use Illuminate\Support\Facades\DB;
use Modules\DataAcuan\Entities\Position;
use Modules\DataAcuan\Entities\Region;
use Modules\DataAcuan\Entities\SubRegion;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Entities\PersonelSupervisorHistory;

/**
 *
 */
trait ChildrenList
{
    use SelfReferenceTrait;

    /* get all children */
    public function getChildren($personel_id) : array
    {
        if ($personel_id) {
            $personels_id = [$personel_id];
            $personel = Personel::with("children.children.children.children")->findOrFail($personel_id);

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
        return [];

    }

    public function getChildrenAplikator($personel_id)
    {
        if ($personel_id) {
            $personels_id = [$personel_id];
            $personel = Personel::with("childrenAplikator")->findOrFail($personel_id);

            foreach ($personel->childrenAplikator as $level1) { //mdm
                $personels_id[] = $level1->id;
            }
            return $personels_id;
        }
        return [];

    }

    /* get all supervisor */
    public function parentPersonel($personel_id, $date = null)
    {
        if ($personel_id) {
            $personels_id = [$personel_id];
            $personel_history = null;
            $supervisor_id = null;

            /*
            |--------------------------------------------------------------
            | check supervisor history of marketing according date
            | on personel_supervisor_hisdtories, if record does
            | not exist, use current supervisor
            |---------------------------------------------------
             */
            do {
                $personel_supervisor_by_date = PersonelSupervisorHistory::query()
                    ->with([
                        "supervisor",
                    ])
                    ->where(function ($QQQ) use ($date, $personel_id) {
                        return $QQQ
                            ->whereNull("supervisor_id")
                            ->orWhereHas("supervisor", function ($QQQ) use ($date, $personel_id) {
                                return $QQQ
                                    ->where("supervisor_id", "!=", $personel_id)
                                    ->orWhere(function ($QQQ) use ($date) {
                                        return $QQQ
                                            ->whereNull("resign_date")
                                            ->orWhere(function ($QQQ) use ($date) {
                                                return $QQQ
                                                    ->whereNotNull("resign_date")
                                                    ->whereDate("resign_date", ">=", $date ? $date : now()->format("Y-m-d"));
                                            });
                                    });
                            });
                    })
                    ->whereDate("change_at", "<=", $date ? $date : now()->format("Y-m-d"))
                    ->where("personel_id", $personel_id)
                    ->orderBy("change_at", "desc")
                    ->whereRaw("personel_id != supervisor_id")
                    ->first();

                if ($personel_supervisor_by_date) {
                    $personels_id[] = $personel_supervisor_by_date->supervisor_id;
                } else {
                    $personel = DB::table('personels')
                        ->whereNull("deleted_at")
                        ->where("id", $personel_id)
                        ->whereRaw("supervisor_id != ?", $personel_id)
                        ->first();
                    $personel_supervisor_by_date = $personel;
                }

                $personels_id[] = $personel_supervisor_by_date?->supervisor_id;
                if ($personel_supervisor_by_date?->supervisor_id) {
                    $personel_id = $personel_supervisor_by_date->supervisor_id;
                } else {
                    break;
                }

            } while ($personel_supervisor_by_date);

            /*
            | PENDING AT THE MOMENT
            |
             */
            // $personel = Personel::find($personel_id);
            // if (!$personel) {
            //     return $personels_id;
            // }
            // $personel = $personel->parent;
            // if ($personel) {
            //     $personels_id[] = $personel->id;
            //     if ($personel->parent) { //rmc
            //         $lvl_1 = $personel->parent;
            //         $personels_id[] = $lvl_1->id;
            //         if ($lvl_1->parent) {
            //             $lvl_2 = $lvl_1->parent;
            //             $personels_id[] = $lvl_2->id;
            //             if ($lvl_2->parent) { //mdm
            //                 $lvl_3 = $lvl_2->parent;
            //                 $personels_id[] = $lvl_3->id;
            //                 if ($lvl_3->parent) { //mm
            //                     $lvl_4 = $lvl_3->parent;
            //                     $personels_id[] = $lvl_4->id;
            //                 }
            //             }
            //         }
            //     }
            //     return $personels_id;
            // }

            return collect($personels_id)
                ->unique()
                ->filter(fn($personel) => $personel)
                ->toArray();
        }
        return [];
    }

    public function marketingLevelAndAbove($area_level, $parent_area_id = null)
    {
        $personel_id = [];
        $personel_mm_temp = null;
        $personel_mdm_temp = null;
        if ($area_level == "marketing_for_region") {
            $position_name_list = [
                "Marketing District Manager (MDM)",
                "Marketing Manager (MM)",
            ];

            $position_id = Position::whereIn("name", $position_name_list)->get()->pluck("id")->toArray();
            $personel_id = Personel::whereIn("position_id", $position_id)->get()->pluck("id")->toArray();
        } else if ($area_level == "marketing_for_sub_region") {
            $position_name_list = [
                "Marketing Manager (MM)",
                "Marketing District Manager (MDM)",
                "Assistant MDM",
                "Regional Marketing Coordinator (RMC)",
            ];
            $region_personel = Region::where("id", $parent_area_id)->first()->personel_id;
            $position_mm = Position::where("name", "Marketing Manager (MM)")->get()->pluck("id")->toArray();
            $personel_mm = Personel::whereIn("position_id", $position_mm)->get()->pluck("id")->toArray();
            if (!in_array($region_personel, $personel_mm)) {
                unset($position_name_list[0]);
            }

            $position_id = Position::whereIn("name", $position_name_list)->get()->pluck("id")->toArray();
            $personel_id = Personel::whereIn("position_id", $position_id)->get()->pluck("id")->toArray();
        } else if ($area_level == "marketing_for_district") {
            $position_name_list = [
                "Marketing Manager (MM)",
                "Marketing District Manager (MDM)",
                "Assistant MDM",
                "Regional Marketing Coordinator (RMC)",
                "Regional Marketing (RM)",
                "Sales Counter (SC)",
            ];

            $sub_region_personel = SubRegion::where("id", $parent_area_id)->first()->personel_id;
            $position_mm = Position::where("name", "Marketing Manager (MM)")->first()->id;
            $personel_mm = Personel::whereIn("position_id", [$position_mm])->get()->pluck("id")->toArray();

            if (!in_array($sub_region_personel, $personel_mm)) {
                if (($key = array_search("Marketing Manager (MM)", $position_name_list)) !== false) {
                    unset($position_name_list[$key]);
                } else {
                    unset($position_name_list[$key]);
                    $personel_mm_temp = $sub_region_personel;
                }
            } else {
                $key = array_search("Marketing Manager (MM)", $position_name_list);
                unset($position_name_list[$key]);
                $personel_mm_temp = $sub_region_personel;
            }

            $position_mdm = Position::where("name", "Marketing District Manager (MDM)")->first()->id;
            $personel_mdm = Personel::whereIn("position_id", [$position_mdm])->get()->pluck("id")->toArray();

            if (!in_array($sub_region_personel, $personel_mdm)) {
                if (($key = array_search("Marketing District Manager (MDM)", $position_name_list)) !== false) {
                    unset($position_name_list[$key]);
                    $personel_mdm_temp = $sub_region_personel;
                } else {
                    unset($position_name_list[$key]);
                    $personel_mdm_temp = $sub_region_personel;
                }
            } else {
                $key = array_search("Marketing District Manager (MDM)", $position_name_list);
                unset($position_name_list[$key]);
                $personel_mdm_temp = $sub_region_personel;
            }
            $position_id = Position::whereIn("name", $position_name_list)->get()->pluck("id")->toArray();
            $personel_id = Personel::whereIn("position_id", $position_id)->get()->pluck("id")->toArray();
            if ($personel_mm_temp) {
                array_push($personel_id, $personel_mm_temp);
            }
            if ($personel_mdm_temp) {
                array_push($personel_id, $personel_mdm_temp);
            }
        }
        return $personel_id;
    }

    public function marketingListForNewAreaBySupervising($area_level, $parent_area_id = null)
    {
        $personel_id = [];
        $personel_mm_temp = null;
        $personel_mdm_temp = null;
        $personels = Personel::query()
            ->whereIn("status", [1, 2]);

        if ($area_level == "marketing_for_region") {
            $position_name_list = [
                "Marketing District Manager (MDM)",
                "Marketing Manager (MM)",
            ];

            $personel_id = $personels
                ->whereHas("position", function ($QQQ) {
                    return $QQQ->whereIn("name", positions_for_region());
                })
                ->get()
                ->pluck("id")
                ->toArray();

        } else if ($area_level == "marketing_for_sub_region") {

            $region_personel = Region::findOrFail($parent_area_id)->personel_id;
            $personel_id = $personels
                ->whereHas("position", function ($QQQ) {
                    return $QQQ->whereIn("name", positions_for_sub_region());
                })
                ->where("supervisor_id", $region_personel)
                ->get()
                ->pluck("id")
                ->toArray();

            array_push($personel_id, $region_personel);

        } else if ($area_level == "marketing_for_district") {
            $sub_region_personel = SubRegion::findOrFail($parent_area_id)->personel_id;
            $personel_id = $personels
                ->whereHas("position", function ($QQQ) {
                    return $QQQ->whereIn("name", positions_for_districts());
                })
                ->where("supervisor_id", $sub_region_personel)
                ->get()
                ->pluck("id")
                ->toArray();

            array_push($personel_id, $sub_region_personel);
        }

        return $personel_id;
    }
    
    public function getChildrenAplikatorV2($personel_id)
    {
        if ($personel_id) {
            $personels_id = [];
            $personels = Personel::with("childrenAplikator")->where('id', $personel_id)->first();
            foreach (($personels->childrenAplikator ?? []) as $level1) { //mdm
                $personels_id[] = $level1->id;
            }
            return $personels_id;
        }
        return [];
    }

    public function getChildrenOneLevel($personel_id)
    {
        if ($personel_id) {
            $personels_id = [$personel_id];
            $personels = Personel::with("children")->where('id', $personel_id)->first();
            // dd($personels);
            foreach (($personels->children ?? []) as $level1) { //mdm
                $personels_id[] = $level1->id;
            }
            return $personels_id;
        }
        return [];
    }

    public function getChildrenV2($personel_id)
    {
        if ($personel_id) {
            $personels_id = [$personel_id];
            $personel = Personel::with("children.children.children.children")->where('id', $personel_id)->first();

            foreach (($personel->children ?? []) as $level1) { //mdm
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
        return [];

    }
}
