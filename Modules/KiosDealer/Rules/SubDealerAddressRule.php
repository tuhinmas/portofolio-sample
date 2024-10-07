<?php

namespace Modules\KiosDealer\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class SubDealerAddressRule implements Rule
{
    protected $messages;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($request, $sub_dealer_id = null)
    {
        $this->request = $request;
        $this->sub_dealer_id = $sub_dealer_id;
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
        elseif ($this->request->status == "accepted") {

            $sub_dealer_submission = DB::table('sub_dealer_temps')
                ->when($this->sub_dealer_id, function ($QQQ) {
                    return $QQQ->where("sub_dealer_id", $this->sub_dealer_id["sub_dealer"]);
                })
                ->when(!$this->sub_dealer_id, function ($QQQ) {
                    return $QQQ->where("status", "filed");
                });

            $sub_dealer_submission = self::queryBaseRequest($sub_dealer_submission, $this->request)
                ->orderByDesc("updated_at")
                ->first();

            if (!$sub_dealer_submission) {
                $this->messages = "this sub dealer has no submission";
                return false;
            }

            /* there is submission */
            else {

                if (in_array($sub_dealer_submission->status, ['filed', 'submission of changes', 'filed rejected', 'change rejected', 'revised', 'revised change'])) {
                    $is_has_valid_address = DB::table('address_with_detail_temps')
                        ->whereNull("deleted_at")
                        ->where("parent_id", $sub_dealer_submission->id)
                        ->get();

                    if (
                        $is_has_valid_address->count() > 2
                        || $is_has_valid_address->count() < 2
                        || $is_has_valid_address->unique("type")->count() > 2
                        || $is_has_valid_address->unique("type")->count() < 2
                    ) {
                        $this->messages = "sub dealer invalid address detail submission";

                        DB::table('sub_dealer_temps')
                            ->where("id", $sub_dealer_submission->id)
                            ->update([
                                "deleted_at" => null,
                            ]);

                        DB::table('address_with_detail_temps')
                            ->whereIn("id", $is_has_valid_address->pluck("id")->toArray())
                            ->whereIn("type", ["sub_dealer", "sub_dealer_owner"])
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
        foreach ($request->except(["status", "status_color", "sub_dealer_id"]) as $attribute => $value) {
            $query->where($attribute, $value);
        };

        return $query;
    }
}
