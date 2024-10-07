<?php

namespace Modules\DataAcuan\Rules;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\Rule;
use Modules\DataAcuan\Entities\DealerGradeSuggestion;

class DealerSuggestGradeUniqueGradeRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($suggested_grading_id, $valid_from, $grade_suggest_id = null)
    {
        $this->suggested_grading_id = $suggested_grading_id;
        $this->grade_suggest_id = $grade_suggest_id;
        $this->valid_from = $valid_from;
    }

    /**
     * unique dealer grade suggestion rule
     *
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        DB::table('dealer_grade_suggestions')
            ->whereNull("deleted_at")
            ->where("grading_id", $value)
            ->sharedLock()
            ->get();

            /* at least dealer suggest unique in date, grading_id and suggested grade */
        $grade_suggest = DealerGradeSuggestion::query()
            ->where("grading_id", $value)
            ->where("suggested_grading_id", $this->suggested_grading_id)
            ->whereDate("valid_from", Carbon::parse( $this->valid_from)->format("Y-m-d"))
            ->when($this->grade_suggest_id, function ($QQQ) {
                return $QQQ->where("id", "!=", $this->grade_suggest_id["dealer_grade_suggestion"]);
            })
            ->first();

        if ($grade_suggest) {
            return false;
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
        return "grading ini sudah memiliki saran grade terpilih untuk tanggal berlaku ini, pilih saran grade lain atau ganti tanggal berlakunya";
    }
}
