<?php

namespace Modules\Personel\Listeners;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Modules\Personel\Entities\Personel;
use Modules\Personel\Events\PersonelActiveEvent;
use Modules\SalesOrder\Entities\SalesOrder;

class MakePersonelActiveListener
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(PersonelActiveEvent $event)
    {
        $personel = $event->personel;

        if ($event->is_new == 1) {
            $clonedPersonel = $personel->replicate();
            $clonedPersonel->resign_date = null;
            $clonedPersonel->join_date = date('Y-m-d');
            $clonedPersonel->push();

            $clonedUser = $personel->user->replicate();
            $clonedUser->personel_id = $clonedPersonel->id;
            $clonedUser->password = Hash::make('javamas1234');
            $clonedUser->deleted_at = null;
            $clonedUser->push();

            $personel->address->each(function($address) use ($clonedPersonel){
                $clonedAddress = $address->replicate();
                $clonedAddress->parent_id = $clonedPersonel->id;
                $clonedAddress->push();
            });

            $personel->personelHasBank->each(function($bank) use ($clonedPersonel){
                $clonedAddress = $bank->replicate();
                $clonedAddress->personel_id = $clonedPersonel->id;
                $clonedAddress->push();
            });

            $personel->contact->each(function($contact) use ($clonedPersonel){
                $clonedAddress = $contact->replicate();
                $clonedAddress->parent_id = $clonedPersonel->id;
                $clonedAddress->push();
            });

            Personel::where('id', $personel->id)->update([
                'personel_id_new' => $clonedPersonel->id,
                'status' => 3
            ]);
            
            return $clonedPersonel;
        }else{
            $personel_user = User::withTrashed()->where("personel_id", $event->personel->id)->first();
            if ($personel_user) {
                $personel_user->restore();
            }

            Personel::find($personel->id)->update([
                'resign_date' => null
            ]);

            SalesOrder::where('personel_id', $personel->id)->whereIn('status', [
                'draft','submited','confirmed',
            ])->where('is_marketing_freeze', 1)->update([
                'is_marketing_freeze' => 0
            ]);

        }
    }
}
