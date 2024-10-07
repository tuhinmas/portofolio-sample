<?php

use Illuminate\Support\Facades\Schema;

if (!function_exists("column_lists")) {
    function column_lists($instance_model)
    {  
       return Schema::getColumnListing($instance_model->getTable());
    }
}