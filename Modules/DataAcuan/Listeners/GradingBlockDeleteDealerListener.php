<?php

namespace Modules\DataAcuan\Listeners;

use Carbon\Carbon;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Personel\Entities\LogFreeze;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use Modules\KiosDealerV2\Entities\DealerV2;

class GradingBlockDeleteDealerListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(DealerV2 $dealer)
    {
        $this->dealer = $dealer;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        /**
         * delete all dealer where grading == grading_id
         */
        $dealerGet = $this->dealer->where("grading_id", $event->grading->grading_id)->update(
            [
                'grading_block_id' => $event->grading->grading_id,
                'is_block_grading' => true,
                'blocked_at' => Carbon::now()->format("Y-m-d"),
                'blocked_by' => Auth::user()->personel_id
                // 'deleted_at' => Carbon::now()
            ]
        );

        // if($dealerGet > 0){
        //     $dealer = $this->dealer->where("grading_id", $event->grading->grading_id)->delete();
        // }

        return $dealerGet;
    }
}
