<?php

namespace Modules\Personel\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Modules\Personel\Entities\Personel;

class PersonelPositionRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($supervisor_id, $personel_id = null)
    {
        $this->supervisor_id = $supervisor_id;
        $this->personel_id = $personel_id;
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
        $position = DB::table('positions')
            ->whereNull("deleted_at")
            ->where("id", $value)
            ->first();

        /* on update personel */
        $personel_id = null;
        if ($this->personel_id) {
            $personel_id = $this->personel_id["personnel"];
            $this->supervisor_id = $this->supervisor_id ? $this->supervisor_id : Personel::findOrFail($personel_id)->supervisor_id;
        }

        /* new personel */
        if ($this->supervisor_id) {
            $supervisor = Personel::query()
                ->with([
                    "position",
                ])
                ->where("id", $this->supervisor_id)
                ->first();

            if ($supervisor?->position_id) {
                if (in_array($position?->name, marketing_positions())) {
                    if (marketing_position_level($supervisor->position->name) <= marketing_position_level($position?->name)) {
                        return false;
                    }
                }
                else {
                    if ($position?->id == $supervisor->position_id) {
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
        return 'Jabatan sama atau tidak sesuai dengan jabatan atasan, Mohon ubah atasan atau ubah jabatan atasan terlebih dahulu';
    }
}
