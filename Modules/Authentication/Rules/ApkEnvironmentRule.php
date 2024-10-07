<?php

namespace Modules\Authentication\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Str;

class ApkEnvironmentRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
        if ($value) {
            if (Str::contains($value, "/public/mobile/apk/")) {
                if (app()->isLocal() || app()->environment('testing')) {
                    return collect(explode("/", $value))->contains("staging");
                } else {
                    return collect(explode("/", $value))->contains(app()->environment());
                }
            }
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
        return 'link must match environment.';
    }
}
