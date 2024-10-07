<?php

namespace App\Traits;

use Illuminate\Support\Str;

/**
 *
 */
trait GmapsLinkGenerator
{
    public function generateGmapsLinkFromLatitude($latitude, $longitude)
    {
        $gmaps_link = "https://www.google.com/maps/place/" . $latitude . "," . $longitude;
        return $gmaps_link;
    }
}
