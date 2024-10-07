<?php

namespace Modules\Authentication\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ApkVersionUniqueRule implements Rule
{

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($environment)
    {
        $this->environment = $environment;
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
        $version = DB::table('mobile_versions')
            ->where("environment", $this->environment)
            ->where("version", $value)
            ->whereNull("deleted_at")
            ->first();

        if (!$version) {
            return true;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'version must unique for environment';
    }
}
