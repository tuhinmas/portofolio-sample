<?php

namespace Modules\Personel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Modules\Personel\Entities\Personel;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Personel\Actions\Applicator\AssignApplicatorToAreaAction;

class PersonelAsApplicatorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

   public $timeout = 300;

   /**
    * The number of times the job may be attempted.
    *
    * @var int
    */
   public $tries = 3;

   /**
    * The maximum number of unhandled exceptions to allow before failing.
    *
    * @var int
    */
   public $maxExceptions = 5;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected Personel $personel)
    {
        
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(AssignApplicatorToAreaAction $applicator_to_area_action)
    {
        $applicator_to_area_action($this->personel);
    }
}
