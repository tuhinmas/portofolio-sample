<?php

namespace Modules\Invoice\Providers;

use Modules\Invoice\Events\FeeMarketingEvent;
use Modules\Invoice\Events\MarketingPointEvent;
use Modules\Invoice\Events\PaymentOnSettleEvent;
use Modules\Invoice\Events\AdjusmentDeletedEvent;
use Modules\Invoice\Events\AdjusmentToOriginEvent;
use Modules\Invoice\Events\ContestPointOriginEvent;
use Modules\Invoice\Events\CreditMemoCanceledEvent;
use Modules\Invoice\Events\FeeTargetMarketingEvent;
use Modules\Invoice\Events\HandOverSalesOrderEvent;
use Modules\Invoice\Events\PaymentStatusCheckEvent;
use Modules\Invoice\Listeners\UpdateLogMarketingFee;
use Modules\Invoice\Events\MarketingPointActiveEvent;
use Modules\Invoice\Listeners\AdjusmentDeletedListener;
use Modules\Invoice\Events\InvoiceNotifDirectSalesEvent;
use Modules\Invoice\Events\InvoiceOnDeletedPaymentEvent;
use Modules\Invoice\Listeners\AdjusmentToOriginListener;
use Modules\Invoice\Listeners\ContestActivePointListener;
use Modules\Invoice\Listeners\HandOverSalesOrderListener;
use Modules\Invoice\Events\FeeMarketingRegulerActiveEvent;
use Modules\Invoice\Listeners\DeliveryStatusCheckListener;
use Modules\Invoice\Listeners\FeeRegulerMarketingListener;
use Modules\Invoice\Listeners\MarketingFeeCounterListener;
use Modules\Invoice\Listeners\MarketingPointActiveListener;
use Modules\Invoice\Listeners\InvoiceNotifDirectSalesListener;
use Modules\Invoice\Listeners\InvoiceOnDeletedPaymentListener;
use Modules\Invoice\Events\SalesOrderOriginDirectGeneratorEvent;
use Modules\Invoice\Listeners\FeeMarketingRegulerActiveListener;
use Modules\Invoice\Listeners\FeeTargetSharingGeneratorListener;
use Modules\Invoice\Listeners\MarketingFeeTargetCounterListener;
use Modules\Invoice\Listeners\FeeRegulerSharingGeneratorListener;
use Modules\Invoice\Listeners\MarketingPointAccumulationListener;
use Modules\Invoice\Listeners\ContestPointOriginGeneratorListener;
use Modules\Invoice\Listeners\FeeRegulerSharingCalculatorListener;
use Modules\Invoice\Listeners\RecalculateMarketingFeeTargetListener;
use Modules\Invoice\Listeners\FeeTargetSharingOriginAsActiveListener;
use Modules\Invoice\Listeners\RecalculateMarketingFeeRegulerListener;
use Modules\Invoice\Listeners\MarketingFeeTargetActiveCounterListener;
use Modules\Invoice\Listeners\SalesOrderOriginDirectGeneratorListener;
use Modules\Invoice\Listeners\CreditMemo\OrderCalculateFeePoinListener;
use Modules\Invoice\Listeners\FeeRegulerSharingOriginGeneratorListener;
use Modules\Invoice\Listeners\UpdateMarketingFeeBaseFeeSharingListener;
use Modules\Invoice\Listeners\FeeRegulerSharingOriginCalculatorListener;
use Modules\Invoice\Events\AdjusmentStockMatchToDistributorContractEvent;
use Modules\Invoice\Listeners\AdjustmentCheckAfterGenerateOriginListener;
use Modules\Invoice\Listeners\MarketingPointPerProductCalculatorListener;
use Modules\Invoice\Listeners\PreviousStockCheckingAfterAdjustmentListener;
use Modules\Invoice\Listeners\PaymentStatusCheckAfterDeletedPaymentListener;
use Modules\Invoice\Listeners\AdjusmentStockMatchToDistributorContractListener;
use Modules\Invoice\Listeners\CreditMemo\OrderRollbackAsNonAffectedReturnListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        InvoiceOnDeletedPaymentEvent::class => [
            InvoiceOnDeletedPaymentListener::class
        ],
        SalesOrderOriginDirectGeneratorEvent::class => [
            SalesOrderOriginDirectGeneratorListener::class,
        ],
        AdjusmentToOriginEvent::class => [
            AdjusmentToOriginListener::class,
            PreviousStockCheckingAfterAdjustmentListener::class
        ],
        ContestPointOriginEvent::class => [
            ContestPointOriginGeneratorListener::class
        ],
        PaymentOnSettleEvent::class => [
            // UpdateMarketingFeeBaseFeeSharingListener::class,
            // UpdateLogMarketingFee::class,
            // ContestActivePointListener::class,

            /* delivery status no need to check payment status */
            // DeliveryStatusCheckListener::class,
            // FeeTargetSharingOriginAsActiveListener::class
        ],
        FeeMarketingEvent::class => [
            FeeRegulerSharingOriginGeneratorListener::class,
            FeeRegulerSharingOriginCalculatorListener::class,
            RecalculateMarketingFeeRegulerListener::class

            /* pending */
            // MarketingFeeCounterListener::class
            // FeeRegulerSharingGeneratorListener::class,
            // FeeRegulerSharingCalculatorListener::class,
        ],
        FeeTargetMarketingEvent::class => [
            
            /**
             * pending
             */
            // FeeTargetSharingGeneratorListener::class,
            // RecalculateMarketingFeeTargetListener::class
            // MarketingFeeTargetCounterListener::class,
            // MarketingFeeTargetActiveCounterListener::class,
        ],
        FeeMarketingRegulerActiveEvent::class => [
            FeeMarketingRegulerActiveListener::class
        ],
        AdjusmentDeletedEvent::class => [
            AdjusmentDeletedListener::class
        ],

        /* adjusment match to distributor contract */
        AdjusmentStockMatchToDistributorContractEvent::class => [
            AdjusmentStockMatchToDistributorContractListener::class
        ],

        /* point marketing */
        MarketingPointEvent::class => [
            MarketingPointPerProductCalculatorListener::class,
            MarketingPointAccumulationListener::class,
        ],

        /* point marketing active */
        MarketingPointActiveEvent::class => [
            MarketingPointActiveListener::class
        ],
        InvoiceNotifDirectSalesEvent::class => [
            InvoiceNotifDirectSalesListener::class
        ],

        /* handover check, and update status fee of order */
        HandOverSalesOrderEvent::class => [
            HandOverSalesOrderListener::class
        ],

        /* payment status check in deleted payment */
        PaymentStatusCheckEvent::class => [
            PaymentStatusCheckAfterDeletedPaymentListener::class
        ],

        /* canceled credit memo, if all memo was canceled */
        CreditMemoCanceledEvent::class => [
            OrderRollbackAsNonAffectedReturnListener::class,
            OrderCalculateFeePoinListener::class
        ]
    ];
}
