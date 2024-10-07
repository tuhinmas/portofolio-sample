<?php

namespace App\Traits;

/**
 * 
 */
trait CollectionResourceWith
{
    public function with($request){
        return [
            "response_code" => "00",
            "response_message" => "success"
        ];
    }
}
