<?php

namespace Modules\KiosDealer\Listeners;

use Modules\DataAcuan\Entities\Grading;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\KiosDealer\Events\DeletedDealerEvent;

class UpdateDealerGradeAfterDeleteListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Grading $grading)
    {
        $this->grading = $grading;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(DeletedDealerEvent $event)
    {
        /* black garde */
        $black_grade = $this->grading->where("name", "hitam")->first();

        if ($black_grade) {
            $event->dealer->grading_id = $black_grade->id;
            $event->dealer->save();
        }
    }
}
