<?php

namespace Modules\Notification\Entities;

use Carbon\Carbon;
use App\Traits\Uuids;
use App\Traits\TimeSerilization;
use Modules\Event\Entities\Event;
use Modules\Invoice\Entities\Invoice;
use Modules\KiosDealer\Entities\Store;
use Illuminate\Database\Eloquent\Model;
use Modules\ForeCast\Entities\ForeCast;
use Modules\Authentication\Entities\User;
use Modules\KiosDealer\Entities\StoreTemp;
use Modules\KiosDealer\Entities\DealerTemp;
use Modules\KiosDealerV2\Entities\DealerV2;
use Modules\KiosDealer\Entities\SubDealerTemp;
use Modules\KiosDealerV2\Entities\SubDealerV2;
use Modules\SalesOrderV2\Entities\SalesOrderV2;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\DistributionChannel\Entities\DeliveryOrder;
use Modules\PlantingCalendar\Entities\PlantingCalendar;

class Notification extends Model
{
    use Uuids;
    use HasFactory;

    public $incrementing = false;
    protected $casts = [
        'id' => 'string',
    ];

    public function setDataAttribute($data)
    {
        $this->attributes['data'] = json_encode($data);
    }

    protected $guarded = [];

    protected static function newFactory()
    {
        return \Modules\Notification\Database\factories\NotificationFactory::new();
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, "notifiable_id", "id")->with("personel");
    }

    public function userFromData()
    {
        return $this->belongsTo(User::class, "data_id", "id");
    }

    public function notificationGroup()
    {
        return $this->belongsTo(NotificationMarketingGroup::class, "notification_marketing_group_id", "id");
    }

    public function salesOrderReviewed()
    {
        return $this->belongsTo(SalesOrderV2::class, "data_id", "id")->where("status", "reviewed");
    }

    public function salesOrderSubmitted()
    {
        return $this->belongsTo(SalesOrderV2::class, "data_id", "id")->where("status", "submited");
    }

    public function salesOrderDraft()
    {
        return $this->belongsTo(SalesOrderV2::class, "data_id", "id")->where("status", "draft");
    }

    public function salesOrderRevised()
    {
        return $this->belongsTo(SalesOrderV2::class, "data_id", "id")->where("status", "revised");
    }

    public function salesOrderRejected()
    {
        return $this->belongsTo(SalesOrderV2::class, "data_id", "id")->where("status", "rejected");
    }

    public function salesOrderConfirmed()
    {
        return $this->belongsTo(SalesOrderV2::class, "data_id", "id")->where("status", "confirmed");
    }

    public function salesOrderCanceled()
    {
        return $this->belongsTo(SalesOrderV2::class, "data_id", "id")->where("status", "canceled");
    }

    public function eventSubmitted()
    {
        return $this->belongsTo(Event::class, "data_id", "id")->where("status", "2");
    }

    public function eventApproveSupervisor()
    {
        return $this->belongsTo(Event::class, "data_id", "id")->where("status", "3");
    }

    public function eventApproveSupport()
    {
        return $this->belongsTo(Event::class, "data_id", "id")->where("status", "14");
    }

    public function eventApproveManajement()
    {
        return $this->belongsTo(Event::class, "data_id", "id")->where("status", "15");
    }

    public function eventApproveReport()
    {
        return $this->belongsTo(Event::class, "data_id", "id")->where("status", "9");
    }

    public function eventCorrectAgenda()
    {
        return $this->belongsTo(Event::class, "data_id", "id")->where("status", "4");
    }

    public function eventCorrectReport()
    {
        return $this->belongsTo(Event::class, "data_id", "id")->where("status", "8");
    }

    public function eventApprovedByMarketing()
    {
        return $this->belongsTo(Event::class, "data_id", "id")->where("status", "16");
    }

    public function eventReportRevisionSupport()
    {
        return $this->belongsTo(Event::class, "data_id", "id")->where("status", "10");
    }

    public function eventReportRejected()
    {
        return $this->belongsTo(Event::class, "data_id", "id")->where("status", "5");
    }

    public function eventRejectEventApprovedBySupport()
    {
        return $this->belongsTo(Event::class, "data_id", "id")->where("status", "12");
    }

    public function eventRejectEventRejectBySupport()
    {
        return $this->belongsTo(Event::class, "data_id", "id")->where("status", "13");
    }

    public function dealerAccepted()
    {
        return $this->belongsTo(DealerV2::class, "data_id", "id")->where("status", "accepted");
    }

    public function dealerRevised()
    {
        return $this->belongsTo(DealerTemp::class, "data_id", "id")->where("status", "revised");
    }

    public function dealerRevisedChange()
    {
        return $this->belongsTo(DealerTemp::class, "data_id", "id")->where("status", "revised change");
    }

    public function dealerFiledRejected()
    {
        return $this->belongsTo(DealerTemp::class, "data_id", "id")->where("status", "filed rejected");
    }

    public function dealerChangeRejected()
    {
        return $this->belongsTo(DealerTemp::class, "data_id", "id")->where("status", "change rejected");
    }

    public function dealerFilledRejected()
    {
        return $this->belongsTo(DealerTemp::class, "data_id", "id")->where("status", "filed rejected");
    }

    public function dealerWaitingApproval()
    {
        return $this->belongsTo(DealerTemp::class, "data_id", "id")->where("status", "wait approval");
    }

    public function subDealerAccepted()
    {
        return $this->belongsTo(SubDealerV2::class, "data_id", "id")->where("status", "accepted");
    }

    public function kios()
    {
        return $this->belongsTo(Store::class, "data_id", "id");
    }

    public function kiosTemp()
    {
        return $this->belongsTo(StoreTemp::class, "data_id", "id");
    }

    public function subDealerChangeRejected()
    {
        return $this->belongsTo(SubDealerTemp::class, "data_id", "id")->where("status", "change rejected");
    }

    public function subDealerFilledRejected()
    {
        return $this->belongsTo(SubDealerTemp::class, "data_id", "id")->where("status", "filed rejected");
    }

    public function subDealerTemp()
    {
        return $this->belongsTo(SubDealerTemp::class, "data_id", "id");
    }

    public function directConfirmed()
    {
        return $this->belongsTo(SalesOrderV2::class, "data_id", "id")->where("status", "confirmed");
    }

    public function directRejected()
    {
        return $this->belongsTo(SalesOrderV2::class, "data_id", "id")->where("status", "rejected");
    }

    public function directReturned()
    {
        return $this->belongsTo(SalesOrderV2::class, "data_id", "id")->where("status", "returned");
    }

    public function plantingCalender()
    {
        return $this->belongsTo(PlantingCalendar::class, "data_id", "id");
    }

    public function foreCast()
    {
        return $this->belongsTo(ForeCast::class, "data_id", "id");
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, "data_id", "id");
    }

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class, "data_id", "id");
    }

    public function scopeEventConditional($query)
    {
        return $query

            /** sales order order indirect */
            ->where(function ($QQQ) {
                return $QQQ
                    ->where("status", "reviewed")
                    ->whereHas("salesOrderReviewed");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "draft")
                    ->whereHas("salesOrderDraft");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "revised")
                    ->whereHas("salesOrderRevised");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "rejected")
                    ->whereHas("salesOrderRejected");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "confirmed")
                    ->whereHas("salesOrderConfirmed");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "canceled")
                    ->whereHas("salesOrderCanceled");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "submited")
                    ->whereHas("salesOrderSubmitted");
            })

            /** event */
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "2")
                    ->whereHas("eventSubmitted");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "3")
                    ->whereHas("eventApproveSupervisor");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "14")
                    ->whereHas("eventApproveSupport");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "15")
                    ->whereHas("eventApproveManajement");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "16")
                    ->whereHas("eventApprovedByMarketing");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "9")
                    ->whereHas("eventApproveReport");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "4")
                    ->whereHas("eventCorrectAgenda");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "8")
                    ->whereHas("eventCorrectReport");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "10")
                    ->whereHas("eventReportRevisionSupport");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "5")
                    ->whereHas("eventReportRejected");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "12")
                    ->whereHas("eventRejectEventApprovedBySupport");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "13");
            })

            /** dealer */
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "accepted")
                    ->whereHas("dealerAccepted");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "change rejected")
                    ->whereHas("dealerChangeRejected");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "filed rejected")
                    ->whereHas("dealerFilledRejected");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "wait approval")
                    ->whereHas("dealerWaitingApproval");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "revised")
                    ->whereHas("dealerRevised");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "revised change")
                    ->whereHas("dealerRevisedChange");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "filed rejected")
                    ->whereHas("dealerFiledRejected");
            })

            /** sub dealer */
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "change rejected")
                    ->whereHas("subDealerChangeRejected");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "accepted")
                    ->whereHas("subDealerAccepted");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "filed rejected")
                    ->whereHas("subDealerFilledRejected");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ->whereHas("subDealerTemp");
            })

            /** direct */
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "confirmed")
                    ->whereHas("directConfirmed");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "rejected")
                    ->whereHas("directRejected");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "returned")
                    ->whereHas("directReturned");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ->whereHas("foreCast");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ->whereHas("invoice");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ->where("status", "counted")
                    ->whereHas("userFromData");
            })

            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "accepted")
                    ->whereHas("kios");
            })
            ->orWhere(function ($QQQ) {
                return $QQQ
                    ->where("status", "filed rejected")
                    ->whereHas("kiosTemp");
            })

            //receiving good
            ->orWhere(function ($QQQ) {
                return $QQQ->whereHas("deliveryOrder");
            });

        // ->orWhereHas("plantingCalender");
    }

    /*
    |-------------------------
    | SCOPE LIST
    |------------------
    |*/
    public function scopeConsideredNotification($query)
    {
        return $query
            ->where(function ($QQQ) {
                return $QQQ
                    ->whereNotNull('expired_at')
                    ->orWhere(function ($QQQ) {
                        return $QQQ
                            ->where("expired_at", ">=", Carbon::now())
                            ->where("read_at", ">=", Carbon::now()->subDay());
                    });
            })
            ->where(function ($QQQ) {
                return $QQQ
                    ->where(function ($QQQ) {
                        return $QQQ
                            ->whereNull("read_at")
                            ->where("expired_at", ">=", Carbon::now());
                    })
                    ->orWhere(function ($QQQ) {
                        return $QQQ
                            ->where("expired_at", ">=", Carbon::now())
                            ->where("read_at", ">=", Carbon::now()->subDay());
                    });
            })
            ->where(function ($query) {
                return $query->eventConditional();
            })
            ->orderBy("created_at", "desc");
    }
}
