<?php

namespace Modules\DataAcuan\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\DataAcuan\Entities\PlantCategory;

class PlantCategoryUniqueRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($category_id = null)
    {
        $this->category_id = $category_id;
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
        $category = PlantCategory::query()
            ->when($this->category_id, function ($QQQ) {
                return $QQQ->where("id", "!=", $this->category_id["plant_category"]);
            })
            ->where("name", $value)
            ->first();

        if ($category) {
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
        return 'category name must unique';
    }
}
