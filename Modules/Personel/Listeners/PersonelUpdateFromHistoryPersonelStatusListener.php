<?php

namespace Modules\Personel\Listeners;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Modules\Personel\Entities\Personel;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Personel\Entities\PersonelStatusHistory;
use Modules\Personel\Actions\Marketing\DeactivateMarketingAction;
use Modules\Personel\Events\PersonelUpdateFromHistoryPersonelStatusEvent;

class PersonelUpdateFromHistoryPersonelStatusListener
{
    public function __construct(protected PersonelStatusHistory $status_history)
    {
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(PersonelUpdateFromHistoryPersonelStatusEvent $event)
    {        
        $personel = $event->personel;
        $deactivate_action = new DeactivateMarketingAction;

        if (($event->entity->status == "1") && ($event->entity->start_date <= Carbon::now()->format("Y-m-d"))) {
            if ($event->entity->is_new == "1" || $event->entity->is_new == true) {

                $clonedPersonel = $personel->replicate();
                $clonedPersonel->status = "1";
                $clonedPersonel->resign_date = null;
                $clonedPersonel->join_date = $event->entity->start_date;
                $clonedPersonel->push();

                $clonedUser = $personel->user->replicate();
                $clonedUser->personel_id = $clonedPersonel->id;
                $clonedUser->password = Hash::make('javamas1234');
                $clonedUser->deleted_at = null;
                $clonedUser->push();

                $personel->address->each(function ($address) use ($clonedPersonel) {
                    $clonedAddress = $address->replicate();
                    $clonedAddress->parent_id = $clonedPersonel->id;
                    $clonedAddress->push();
                });

                $personel->personelHasBank->each(function ($bank) use ($clonedPersonel) {
                    $clonedAddress = $bank->replicate();
                    $clonedAddress->personel_id = $clonedPersonel->id;
                    $clonedAddress->push();
                });

                $personel->contact->each(function ($contact) use ($clonedPersonel) {
                    $clonedAddress = $contact->replicate();
                    $clonedAddress->parent_id = $clonedPersonel->id;
                    $clonedAddress->push();
                });

                Personel::where('id', $personel->id)->update([
                    'personel_id_new' => $clonedPersonel->id,
                    'resign_date' => $event->entity->start_date,
                    'status' => 3,
                ]);

                $this->status_history->create([
                    "start_date" => $personelstatushistory->start_date,
                    "status" => "1",
                    "personel_id" => $new_marketing->id,
                    "is_new" => 0,
                    "is_checked" => 1,
                ]);

                $event->entity->is_checked = "1";
                $event->entity->save();
            }

            if ($event->entity->is_new == "0" || $event->entity->is_new == false) {
                // dd($personel);
                $personel->join_date = $event->entity->start_date;
                $personel->resign_date = null;
                $personel->status = "1";
                $personel->save();

                $event->entity->is_checked = "1";
                $event->entity->save();
            }
        }

        if ($event->entity->status == "2" && ($event->entity->start_date <= Carbon::now()->format("Y-m-d"))) {
            $personel->status = $event->entity->status;
            $personel->save();

            $event->entity->is_checked = "1";
            $event->entity->save();
        }

        if (($event->entity->status == "3") && ($event->entity->start_date <= Carbon::now()->format("Y-m-d"))) {
            $personel->resign_date = $event->entity->start_date;
            $personel->status = $event->entity->status;
            $personel->save();

            $event->entity->is_checked = "1";
            $event->entity->save();
            $status_before = PersonelStatusHistory::query()
                ->where("personel_id", $personel->id)
                ->where("start_date", "<", $event->entity->start_date)
                ->orderByDesc('start_date', 'desc')
                ->orderByDesc('created_at', 'desc')
                ->first();

            if ($status_before) {
                if (!$status_before->end_date) {
                    $status_before->end_date = Carbon::parse($event->entity->start_date)->subDays(1);
                    $status_before->save();
                }
            }
            /**
             * deactivate action
             */
            $deactivate_action($personel, $status_before?->status);
        }
    }
}
