<?php

namespace Modules\Invoice\Providers;

use Modules\Invoice\Entities\Invoice;
use Modules\Invoice\Entities\Payment;
use Illuminate\Support\ServiceProvider;
use Modules\Invoice\Policies\InvoicePolicy;
use Modules\Invoice\Policies\PaymentPolicy;
use Modules\Invoice\Entities\InvoiceProforma;
use Modules\Invoice\Entities\EntrusmentPayment;
use Modules\Invoice\Policies\EntrusmentPaymentPolicy;
use Modules\InvoiceProforma\Policies\InvoiceProformaPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider;
use Modules\Contest\Policies\PointAdjusmentPolicy;
use Modules\Invoice\Entities\AdjustmentStock;

class AuthInvoiceProvide extends AuthServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Invoice::class => InvoicePolicy::class,
        Payment::class => PaymentPolicy::class,
        EntrusmentPayment::class => EntrusmentPaymentPolicy::class,
        InvoiceProforma::class => InvoiceProformaPolicy::class,
        AdjustmentStock::class => PointAdjusmentPolicy::class
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
