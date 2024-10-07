<?php

namespace App\Traits;

/**
 * search and filter base on Orion package
 */
trait Search
{
    /**
     * scope data
     *
     * @param [type] $query
     * @param [type] $parameter
     * @return void
     */
    public function scopes($query, $parameter = null){

    }

    /**
     * filters
     *
     * @param [type] $query
     * @param [type] $request
     * @return void
     */
    public function filters($query, $request){
        foreach ($request->filters as $key => $filter) {
            // $query = $query->where($filter["field"], $filter["operator"], $filter["value"]);

            if (is_array($filter)) {
                if ($key == 0) {
                    $query = $query->where($filter["field"], $filter["operator"], $filter["value"]);
                }else {
                    $array_keys = array_keys($filter);
                    if (in_array("type", $array_keys)) {
                        $query = $query->orWhere($filter["field"], $filter["operator"], $filter["value"]);
                    }else{
                        $query = $query->where($filter["field"], $filter["operator"], $filter["value"]);
                    }
                }
            }
        }
        return $query;
    }
}
