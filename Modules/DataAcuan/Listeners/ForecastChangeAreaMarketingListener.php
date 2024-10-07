<?php

namespace Modules\DataAcuan\Listeners;

use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\DataAcuan\Events\ForecastChangeAreaMarketingEvent;
use Modules\DataAcuan\Events\MarketingAreaOnChangeEvent;
use Modules\ForeCast\Entities\ForeCast;
use Modules\ForeCast\Entities\ForecastHistory;
use Modules\Personel\Entities\Personel;

class ForecastChangeAreaMarketingListener
{
    public function handle(ForecastChangeAreaMarketingEvent $event)
    {
        $data = $event->personel_id_change;
        foreach ($data as $key => $value) {
            if (!empty($value['marketing_area_district'])) {
                $marketingAreaDistrict = MarketingAreaDistrict::find($value['marketing_area_district']);
                $findAllForecast = ForeCast::where(function($q) use($marketingAreaDistrict){
                    $q->whereHas('dealer.areaDistrictStore', function($q) use($marketingAreaDistrict){
                        $q->where('address_with_details.district_id', $marketingAreaDistrict->district_id);
                    })->orWhereHas('subDealerV2.areaDistrictStore', function($q) use($marketingAreaDistrict){
                        $q->where('address_with_details.district_id', $marketingAreaDistrict->district_id);
                    });
                })->whereNotNull('dealer_category')->where('date','>=', date('Y-m'))->get();
    
                $personelIdAfter = $value['personel_id_after'];
                foreach ($findAllForecast as $key => $row) {
                    if (!empty($personelIdAfter)) {
                        ForecastHistory::create([
                            "dealer_category" => $row->dealer_category,
                            "dealer_id" => $row->dealer_id,
                            "change_by" => auth()->user()->personel_id,
                            "personel_id" => $value['personel_id_after'],
                            "product_id" => $row->product_id,
                            "forecast_id" => $row->id,
                            "unit" => $row->unit,
                            "status" => $row->status,
                            "price" => $row->price,
                            "quantity" => $row->quantity,
                            "nominal" => $row->nominal,
                        ]);
    
                        $row->update([
                            "personel_id" => $personelIdAfter
                        ]);
                    }
                }
            }
        }
    }
}
