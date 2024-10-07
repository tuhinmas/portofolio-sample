<?php

namespace Modules\KiosDealer\Providers;

use Modules\KiosDealer\Events\DeletedDealerEvent;
use Modules\KiosDealer\Events\StoreOnUpdateEvent;
use Modules\KiosDealer\Events\DealerActivatedEvent;
use Modules\KiosDealer\Events\SubDealerNotifAccepted;
use Modules\KiosDealer\Events\DealerNotifAcceptedEvent;
use Modules\KiosDealer\Events\DealerFilledRejectedEvent;
use Modules\KiosDealer\Events\StoreTempConfirmationEvent;
use Modules\KiosDealer\Events\SubDealerNotifAcceptedEvent;
use Modules\KiosDealer\Listeners\StoreAsSubDealerListener;
use Modules\KiosDealer\Events\SubDealerFilledRejectedEvent;
use Modules\KiosDealer\Events\DealerNotifChangeRejectedEvent;
use Modules\KiosDealer\Listeners\DealerNotifAcceptedListener;
use Modules\KiosDealer\Events\DealerNotifWaitingApprovalEvent;
use Modules\KiosDealer\Listeners\StoreTempConfirmationListener;
use Modules\KiosDealer\Events\DealerSubmissionNotificationEvent;
use Modules\KiosDealer\Events\SubDealerNotifChangeRejectedEvent;
use Modules\KiosDealer\Listeners\SubDealerNotifAcceptedListener;
use Modules\KiosDealer\Listeners\DealerNotifChangeRejectedListener;
use Modules\KiosDealer\Listeners\DealerNotifFilledRejectedListener;
use Modules\KiosDealer\Listeners\DealerNotifWaitingApprovalListener;
use Modules\KiosDealer\Listeners\SubDealerRollbackToAcceptedListener;
use Modules\KiosDealer\Listeners\DealerSubmissionNotificationListener;
use Modules\KiosDealer\Listeners\SubDealerNotifChangeRejectedListener;
use Modules\KiosDealer\Listeners\SubDealerNotifFilledRejectedListener;
use Modules\KiosDealer\Listeners\UpdateDealerGradeAfterDeleteListener;
use Modules\KiosDealer\Listeners\NotificationOnStoreSubmissionListener;
use Modules\KiosDealer\Events\SubDealerRegisteredAsDealerInContestEvent;
use Modules\KiosDealer\Listeners\UpdateDealerGradeAfterActivatedListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\KiosDealer\Events\DealerChangeHistoryEvent;
use Modules\KiosDealer\Events\DealerNotifChangeAcceptedEvent;
use Modules\KiosDealer\Events\DealerNotifRevisedDataChangeEvent;
use Modules\KiosDealer\Events\DealerNotifRevisedEvent;
use Modules\KiosDealer\Events\DealerNotifWaitingApprovalDataChangeEvent;
use Modules\KiosDealer\Listeners\DealerChangeHistoryListener;
use Modules\KiosDealer\Listeners\DealerNotifChangeAcceptedListener;
use Modules\KiosDealer\Listeners\DealerNotifRevisedDataChangeListener;
use Modules\KiosDealer\Listeners\DealerNotifRevisedListener;
use Modules\KiosDealer\Listeners\DealerNotifWaitingApprovalDataChangeListener;
use Modules\KiosDealer\Listeners\SubDealerRegisteredAsDealerReplaceContestParticipantListener;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        StoreTempConfirmationEvent::class => [
            StoreTempConfirmationListener::class
        ],
        SubDealerRegisteredAsDealerInContestEvent::class => [
            StoreAsSubDealerListener::class,
            SubDealerRegisteredAsDealerReplaceContestParticipantListener::class
        ],
        DeletedDealerEvent::class => [
            UpdateDealerGradeAfterDeleteListener::class
        ],
        DealerActivatedEvent::class => [
            UpdateDealerGradeAfterActivatedListener::class
        ],
        DealerNotifWaitingApprovalEvent::class => [
            DealerNotifWaitingApprovalListener::class
        ],
        DealerNotifWaitingApprovalDataChangeEvent::class => [
            DealerNotifWaitingApprovalDataChangeListener::class     
        ],
        DealerNotifAcceptedEvent::class => [
            DealerNotifAcceptedListener::class
        ],
        DealerChangeHistoryEvent::class => [
            DealerChangeHistoryListener::class
        ],
        DealerNotifChangeAcceptedEvent::class => [
            DealerNotifChangeAcceptedListener::class
        ],
        DealerFilledRejectedEvent::class => [
            DealerNotifFilledRejectedListener::class,
            SubDealerRollbackToAcceptedListener::class
        ],
        DealerNotifChangeRejectedEvent::class => [
            DealerNotifChangeRejectedListener::class,
            SubDealerRollbackToAcceptedListener::class
        ],
        DealerNotifRevisedEvent::class => [
            DealerNotifRevisedListener::class
        ],
        SubDealerNotifAcceptedEvent::class => [
            SubDealerNotifAcceptedListener::class
        ],
        SubDealerFilledRejectedEvent::class => [
            SubDealerNotifFilledRejectedListener::class
        ],
        SubDealerNotifChangeRejectedEvent::class => [
            SubDealerNotifChangeRejectedListener::class
        ],
        StoreOnUpdateEvent::class => [
            NotificationOnStoreSubmissionListener::class
        ],
        DealerNotifRevisedDataChangeEvent::class => [
            DealerNotifRevisedDataChangeListener::class
        ]
    ];
}
