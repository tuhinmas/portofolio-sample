<?php
namespace App\Traits;

use Modules\Personel\Entities\Personel;

/**
 *
 */
trait SuperVisorCheckV2
{
    /**
     * get personel subordinate by supervisor id
     *
     * @return void
     */
    private function getPersonel($personel_id = null)
    {
        $personel_id = $personel_id ?: auth()->user()->personel_id;

        $personels = [$personel_id];
        if (auth()->user()->hasAnyRole("Marketing District Manager (MDM)")) {
            $personels = Personel::where('supervisor_id', $personel_id)->orWhere('id', $personel_id)->pluck('id')->toArray();
        }
        
        $personel = Personel::whereIn('supervisor_id', $personels)->with(['personelUnder'])->get();
        
        $level_one = $personel->pluck('id')->toArray();
        if (!empty($level_one)) {
            $personels = array_merge($personels, $level_one);
        }
        
        $level_two = array_filter(collect($personel)->map(function ($q) {
            return collect($q->personelUnder)->pluck('id');
        })
        ->toArray());   


        foreach ($level_two as $val) {
            if (!empty($val)) {
                $personels = array_merge($personels, $val);
            }
        }
        return $personels;
    }
    
    /**
     * get personel subordinate by supervisor id
     *
     * @return void
     */
    private function getPersonelChild($personel_id)
    {
        $personels = [$personel_id];

        if (auth()->user()->hasAnyRole("Marketing District Manager (MDM)")) {
            $personels = Personel::where('supervisor_id', $personel_id)->orWhere('id', $personel_id)->pluck('id')->toArray();
        }
        
        $personel = Personel::whereIn('supervisor_id', $personels)->with(['personelUnder'])->get();
        
        $level_one = $personel->pluck('id')->toArray();
        if (!empty($level_one)) {
            $personels = array_merge($personels, $level_one);
        }
        
        $level_two = array_filter(collect($personel)->map(function ($q) {
            return collect($q->personelUnder)->pluck('id');
        })
        ->toArray());   


        foreach ($level_two as $val) {
            if (!empty($val)) {
                $personels = array_merge($personels, $val);
            }
        }
        return $personels;
    }
}
