<?php

namespace Modules\Personel\Actions\Point;

use Modules\PointMarketing\ClassHelper\PointMarketingRule;
use Modules\Personel\Actions\Order\GetMarketingOrderYearAction;
use Modules\Personel\Actions\Point\RecalculateMarketingPointPerYearAction;
use Modules\Personel\Actions\Point\CalculateMarketingPointPerProductAction;

class CalculateMarketingPointByOrderAction
{
    public function __invoke($sales_order)
    {
        if ($sales_order->status == "confirmed") {
            $order_to_get_point_rule = new PointMarketingRule();
            $recalculate_point_action = new RecalculateMarketingPointPerYearAction();
            if ((new PointMarketingRule)->isConsideredOrderToGetPoint($sales_order)) {

                /* calculated poin marketing per product */
                (new CalculateMarketingPointPerProductAction)($sales_order);

                /* recalculte amrekting point in the year */
                $marketing_sales_in_the_year = (new GetMarketingOrderYearAction)($sales_order->personel_id, confirmation_time($sales_order)->year);
                $recalculate_point_action($sales_order->personel_id, confirmation_time($sales_order)->year, $marketing_sales_in_the_year);

                return "marketing point calculated";
            }

            return "order not considered to get point";
        }
        return 0;
    }
}
