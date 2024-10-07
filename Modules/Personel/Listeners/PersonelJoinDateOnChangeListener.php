<?php

namespace Modules\Personel\Listeners;

use App\Traits\ChildrenList;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Events\PersoneJoinDateEvent;
use Modules\Personel\Traits\FeeMarketingTrait;
use Modules\SalesOrder\Entities\FeeSharingSoOrigin;
use Modules\SalesOrder\Entities\FeeTargetSharingSoOrigin;
use Modules\SalesOrder\Entities\SalesOrder;
use Modules\SalesOrder\Entities\SalesOrderDetail;

class PersonelJoinDateOnChangeListener
{
    use FeeMarketingTrait;
    use ChildrenList;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        FeeTargetSharingSoOrigin $fee_target_sharing_origin,
        FeeSharingSoOrigin $fee_sharing_origin,
        SalesOrderDetail $sales_order_detail,
        MarketingFee $marketing_fee,
        SalesOrder $sales_order,
        Personel $personel,
    ) {
        $this->fee_target_sharing_origin = $fee_target_sharing_origin;
        $this->sales_order_detail = $sales_order_detail;
        $this->fee_sharing_origin = $fee_sharing_origin;
        $this->marketing_fee = $marketing_fee;
        $this->sales_order = $sales_order;
        $this->personel = $personel;
        $this->year = now()->format("Y");
        $this->quarter = now()->quarter;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(PersoneJoinDateEvent $event)
    {
    
        /*
        |---------------------------------------
        | UPDATE PERSONEL_ID FEE SHARING
        |---------------------------------
        |*/
        $fee_sharing_update = $this->feeSharingRegulerSpesificMarketing($event->personel, $this->year, $this->quarter);

        /* fee sharing data mapping */
        $fee_sharing_mapping_data = $this->feeSharingOriginDataMapping($fee_sharing_update);

        /*
        |---------------------------------------------
        | UPDATE PERSONEL_ID FEE TARGET SHARING
        |----------------------------------------
        */
        $fee_target_sharing_update = $this->feeSharingTargetSpesificMarketing($event->personel, $this->year, $this->quarter);

        /* fee targetv sharing data mapping */
        $fee_target_sharing_mapping_data = $this->feeTargetSharingOriginDataMapping($fee_target_sharing_update);

        return [
            "fee_sharing_count" => $fee_sharing_mapping_data->count(),
            "fee_target_sharing_count" => $fee_target_sharing_mapping_data->count()
        ];
    }
}
