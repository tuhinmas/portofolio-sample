<?php

namespace Modules\Invoice\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\ResponseHandlerV2;
use Illuminate\Support\Facades\Bus;
use Modules\Invoice\Entities\Invoice;
use Modules\Invoice\Entities\Payment;
use Orion\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Orion\Concerns\DisableAuthorization;
use Illuminate\Database\Eloquent\Builder;
use Modules\Authentication\Entities\User;
use Modules\Invoice\Http\Requests\PaymentRequest;
use Modules\Invoice\Transformers\PaymentResource;
use Modules\Invoice\Events\FeeTargetMarketingEvent;
use Modules\Invoice\Events\PaymentStatusCheckEvent;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Invoice\Events\MarketingPointActiveEvent;
use Modules\Invoice\Events\InvoiceOnDeletedPaymentEvent;
use Modules\Contest\Events\UpdatedStatusParticipationEvent;
use Modules\Contest\Jobs\ContestPointCalculationByOrderJob;
use Modules\Invoice\Jobs\CalculateMarketingFeeOnPaymentJob;
use Modules\Invoice\Transformers\PaymentCollectionResource;
use Modules\Invoice\Jobs\GenerateFeeTargetNomimalSharingJob;
use Modules\SalesOrderV2\Jobs\MarketingPointCalculationByOrderJob;
use Modules\SalesOrderV2\Jobs\CalculateMarketingFeeTargetByOrderJob;

class PaymentController extends Controller
{
    use DisableAuthorization;
    use ResponseHandlerV2;

    protected $model = Payment::class;
    protected $request = PaymentRequest::class;
    protected $resource = PaymentResource::class;
    protected $collectionResource = PaymentCollectionResource::class;

    /**
     * include data relation
     */
    public function alwaysIncludes(): array
    {
        return [
            "invoice",
            "personel",
        ];
    }

    /**
     * The attributes that are used for filtering.
     *
     * @return array
     */
    public function filterableBy(): array
    {
        return [
            'id',
            'invoice_id',
            'nominal',
            'user_id',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Fills attributes on the given entity and stores it in database.
     *
     * @param Request $request
     * @param Model $entity
     * @param array $attributes
     */
    protected function performStore(Request $request, Model $entity, array $attributes): void
    {
        $entity->fill($attributes);
        $entity->save();
        $invoice = Invoice::query()
            ->with([
                "payment" => function ($QQQ) {
                    return $QQQ
                        ->orderBy("created_at", "desc")
                        ->orderBy("created_at", "desc");
                },
                "salesOrderOnly",
            ])
            ->findOrFail($request->invoice_id);

        if (floatval($request->remaining_payment) <= 0) {

            if ($invoice->payment_status != "settle") {

                /**
                 * point marketing active
                 */
                $marketing_point_active = MarketingPointActiveEvent::dispatch($invoice->salesOrder, $entity);
            }
            $invoice->payment_status = "settle";
            $invoice->save();
        } else {
            $invoice->payment_status = "paid";
            $invoice->save();
        }
        $payment = Payment::where('id', $entity->id)->with('invoice')->first();
        $entity = $payment;
    }

    /**
     * check invoice before save it
     *
     * @param Request $request
     * @param [type] $payment
     * @return void
     */
    public function beforeStore(Request $request, $payment)
    {
        $invoice = Invoice::findOrFail($request->invoice_id);
        $user = User::findOrFail($request->user_id);
        $payment->invoice()->associate($invoice);
    }

    public function afterStore(Request $request, $model)
    {
        /**
         * include fee marketing, pooint contest
         */
        Bus::chain([

            /* fee reguler active marketing */
            new CalculateMarketingFeeOnPaymentJob($model->invoice->salesOrder, $save_log = true),

            /* fee target marketing, update fee target active */
            new GenerateFeeTargetNomimalSharingJob($model->invoice->salesOrder),
            new CalculateMarketingFeeTargetByOrderJob($model->invoice->salesOrder),

            /* marketing point calculation */
            new MarketingPointCalculationByOrderJob($model->invoice->salesOrder),

            new ContestPointCalculationByOrderJob($model->invoice->salesOrder),
        ])->dispatch();

        /**
         * fee target marketing in this quarter
         * total and active
         */
        $fee_target_marketing = FeeTargetMarketingEvent::dispatch($model->invoice);

        UpdatedStatusParticipationEvent::dispatch();
    }

    /**
     * Builds Eloquent query for fetching entity(-ies).
     *
     * @param Request $request
     * @param array $requestedRelations
     * @return Builder
     */
    protected function buildDestroyFetchQuery(Request $request, array $requestedRelations, bool $softDeletes): Builder
    {
        $query = parent::buildDestroyFetchQuery($request, $requestedRelations, $softDeletes);
        return $query;
    }

    /**
     * Runs the given query for fetching entity.
     *
     * @param Request $request
     * @param Builder $query
     * @param int|string $key
     * @return Model
     */
    protected function runDestroyFetchQuery(Request $request, Builder $query, $key): Model
    {
        return $query->findOrFail($key);
    }

    /**
     * Fills attributes on the given entity and stores it in database.
     *
     * @param Request $request
     * @param Model $post
     * @param array $attributes
     */
    protected function performDestroy(Model $entity): void
    {
        $entity->delete();
    }

    public function beforeDestroy(Request $request, $entity)
    {
        $payment_count = Payment::query()
            ->where("invoice_id", $entity->invoice_id)
            ->get();

        self::deletationRules($entity, $payment_count);
        if ($payment_count->count() == 1) {

            /**
             * if payment is only one then
             * set invoice to unpaid
             */
            $payment = InvoiceOnDeletedPaymentEvent::dispatch($entity);
        }
    }

    public function afterDestroy(Request $request, $model)
    {
        /* payment status check */
        $payment_check = PaymentStatusCheckEvent::dispatch($model);
    }

    public function deletationRules($payment, $payments)
    {
        $last_payment = $payments
            ->filter(fn($payment) => $payment->is_credit_memo && $payment->nominal > 0)
            ->sortByDesc("created_at")
            ->first();

        switch (true) {
            case $payment->is_credit_memo:
                $response = $this->response("04", "invlaid data send", [
                    "message" => "payment dari kredit memo tidak bisa dihapus",
                ], 422);
                throw new HttpResponseException($response);
                break;

            case $last_payment && $payment->created_at <  $last_payment?->created_at:
                $response = $this->response("04", "invlaid data send", [
                    "message" => "payment tidak memnuhi syarat untuk dihapus, ada kredit memo setelahnya",
                ], 422);
                throw new HttpResponseException($response);
                break;

            default:
                break;
        }

    }
}
