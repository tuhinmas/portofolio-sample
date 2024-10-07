<?php

namespace Modules\Personel\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Personel\Entities\MarketingFee;
use Modules\Personel\Entities\PersonelNote;
use Modules\Personel\Policies\MarketingFeePolicy;
use Modules\Personel\Policies\PersonelNotePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;

class AuthPersonelServiceProvider extends AuthServiceProvider
{
      /* The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        PersonelNote::class => PersonelNotePolicy::class,
        MarketingFee::class => MarketingFeePolicy::class,
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
