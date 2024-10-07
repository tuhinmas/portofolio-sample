<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Ladumor\OneSignal\OneSignal;

class OneSignalJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fields, $message;

    public $timeout = 1000;

    public $maxExceptions = 2;

    public function __construct($fields, $message)
    {
        $this->fields = $fields;
        $this->message = $message;
    }

    public function handle()
    {
        return OneSignal::sendPush($this->fields, $this->message);
    }

}