<?php

namespace Modules\Personel\Actions\Point;

use Modules\DataAcuan\Entities\PointProduct;

class GetPointProductByYearPerProductAction
{
    /**
     * get point poduct reference by year per product
     *
     * @param [string] $product_id
     * @param [int] $year
     * @return void
     */
    public function __invoke($product_id, $year = null)
    {
        return PointProduct::query()
            ->where("product_id", $product_id)
            ->where("year", ($year ? $year : now()->year))
            ->get();
    }
}
