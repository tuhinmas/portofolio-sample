<?php

namespace Modules\KiosDealer\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class DealerAddressRule implements Rule
{
    protected $messages;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($request, $dealer_id = null)
    {
        $this->request = $request;
        $this->dealer_id = $dealer_id;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($this->request->status != "accepted") {
            return true;
        }

        /* accepted status need to check submission */
        if ($this->request->status == "accepted") {

            $dealer_submission = DB::table('dealer_temps')
                ->when($this->dealer_id, function ($QQQ) {
                    return $QQQ
                        ->whereNotNull("dealer_id")
                        ->where("dealer_id", $this->dealer_id["dealer"]);
                })
                ->when(!$this->dealer_id, function ($QQQ) {
                    return $QQQ->whereNull("dealer_id");
                });

            $dealer_submission = self::queryBaseRequest($dealer_submission, $this->request)
                ->orderByDesc("updated_at")
                ->first();

            if (!$dealer_submission) {
                $this->messages = "this dealer has no submission";
                return false;
            } else {
                if (in_array($dealer_submission->status, ['filed', 'submission of changes', 'wait approval', 'revised', 'revised change'])) {
                    $is_has_valid_address = DB::table('address_with_detail_temps')
                        ->whereNull("deleted_at")
                        ->where("parent_id", $dealer_submission->id)
                        ->get();

                    if (
                        $is_has_valid_address->count() > 2
                        || $is_has_valid_address->count() < 2
                        || $is_has_valid_address->unique("type")->count() > 2
                        || $is_has_valid_address->unique("type")->count() < 2
                    ) {
                        $this->messages = "dealer invalid address detail submission";

                        DB::table('dealer_temps')
                            ->where("id", $dealer_submission->id)
                            ->update([
                                "deleted_at" => null,
                            ]);

                        DB::table('address_with_detail_temps')
                            ->whereIn("id", $is_has_valid_address->pluck("id")->toArray())
                            ->whereIn("type", ["dealer", "dealer_owner"])
                            ->update([
                                "deleted_at" => null,
                            ]);

                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->messages;
    }

    public static function queryBaseRequest($query, $request): Builder
    {
        foreach ($request->except(["status", "status_color", "dealer_id"]) as $attribute => $value) {
            $query->where($attribute, $value);
        };

        return $query;
    }
}
