<?php

namespace Modules\ReceivingGood\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\ReceivingGood\Entities\ReceivingGood;
use Modules\ReceivingGood\Entities\ReceivingGoodFile;
use Modules\ReceivingGood\Policies\ReceivingGoodPolicy;
use Modules\ReceivingGood\Policies\ReceivingGoodFilePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;

class AuthReceivingGoodProvider extends ServiceProvider
{
    /* The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        ReceivingGood::class => ReceivingGoodPolicy::class,
        ReceivingGoodFile::class => ReceivingGoodFilePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
