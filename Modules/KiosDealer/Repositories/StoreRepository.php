<?php

namespace Modules\KiosDealer\Repositories;

use Modules\KiosDealer\Entities\Store;

class StoreRepository {

    public function checkRequestOfChange($storeId)
    {
        $store = Store::query()
            ->where('id', $storeId)
            ->where(function($q){
                $q->whereIn('status', ['submission of changes', 'transfered'])
                ->orWhereNotNull('dealer_id')
                ->orWhereNotNull('sub_dealer_id')
                ->orHas('dealerTemp')
                ->orHas('subDealerTemp');
            })
            ->first();

        $messageType = 0;
        $response = [
            'can_edit' => true,
            'message' => 'yeee, You can edit this store',
            'message_type' => $messageType
        ];   
        
        if ($store) {
            if (in_array($store->status, ['submission of changes', 'transfered']) || $store->dealerTemp || $store->subDealerTemp) {
                $message = "Kios ".$store->name.' sedang dalam pengajuan perubahan atau transfered';
                $messageType = 1;
                if ($store->dealerTemp) {
                    $message = "Kios ".$store->name.' sedang dalam pengajuan menjadi dealer';
                    $messageType = 2;
                }elseif($store->subDealerTemp){
                    $message = "Kios ".$store->name.' sedang dalam pengajuan menjadi sub dealer';
                    $messageType = 3;
                }
            }elseif ($store->dealer_id != null) {
                $message = "Kios ".$store->name.' sudah pernah diajukan sebagai Dealer';
                $messageType = 4;
            }elseif ($store->sub_dealer_id != null) {
                $message = "Kios ".$store->name.' sudah pernah diajukan sebagai Sub Dealer';
                $messageType = 5;
            }

            $response = [
                'can_edit' => false,
                'message' => $message,
                'message_type' => $messageType
            ];
        }
        
        return $response;

    }

}
