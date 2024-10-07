<?php

namespace Modules\KiosDealer\Listeners;

use Modules\DataAcuan\Entities\Grading;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\KiosDealer\Events\DealerActivatedEvent;

class UpdateDealerGradeAfterActivatedListener
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
    public function handle(DealerActivatedEvent $event)
    {
        /* red grade */
        $red_grade = $this->grading->where("name", "merah")->first();
        if ($red_grade) {
            $event->dealer->grading_id = $red_grade->id;
            $event->dealer->save();
        }
    }
}
