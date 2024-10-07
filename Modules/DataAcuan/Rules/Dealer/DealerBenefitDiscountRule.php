<?php

namespace Modules\DataAcuan\Rules\Dealer;

use Illuminate\Contracts\Validation\Rule;

class DealerBenefitDiscountRule implements Rule
{
    protected $messages;

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
        if (collect($value)->count() == 0) {
            return false;
        }

        $first_stage_must_always = true;
        switch (true) {
            case !collect($value[0])->has("type"):
                $this->messages = "stage 1 harus punya tipe dicount";
                $first_stage_must_always = false;
                break;

            case !collect($value[0])->has("stage"):
                $this->messages = "stage 1 harus punya stage dicount";
                $first_stage_must_always = false;
                break;
            case !collect($value[0])->has("discount"):
                $this->messages = "stage 1 harus punya dicount";
                $first_stage_must_always = false;
                break;

            case !collect($value[0])->has("product_category"):
                $this->messages = "stage 1 harus punya kategori produk (product_category)";
                $first_stage_must_always = false;
                break;

            case !collect($value[0])->has("sibling_discount"):
                $this->messages = "stage 1 harus punya diskon bersama (sibling_discount)";
                $first_stage_must_always = false;
                break;

            default:
                break;
        }

        if (!$first_stage_must_always) {
            return false;
        }

        switch (true) {
            case collect($value)->first()["type"] != "always":
                $this->messages = "stage 1 tipe dicount harus always";
                $first_stage_must_always = false;
                break;

            case collect($value)->first()["stage"] != "1":
                $this->messages = "stagenya harunya bernilai 1";
                $first_stage_must_always = false;
                break;

            case !is_array($value[0]["discount"]):
                $this->messages = "stage 1 attribute discount harus array";
                $first_stage_must_always = false;
                break;

            case !is_array($value[0]["product_category"]):
                $this->messages = "stage 1 attribute product_category harus array";
                $first_stage_must_always = false;
                break;

            default:
                break;
        }

        if (!$first_stage_must_always) {
            return false;
        }

        if (count($value) > 1) {

            
            $tresshold_rule = true;
            collect($value)
                ->filter(fn($benefit, $index) => $index != "0")
                ->each(function ($benefit, $index) use (&$tresshold_rule, $value) {

                    switch (true) {
                        case !collect($value[$index])->has("type"):
                            $this->messages = "stage " . ($index + 2) . "harus punya tipe dicount";
                            $tresshold_rule = false;
                            break;

                        case !collect($value[$index])->has("stage"):
                            $this->messages = "stage " . ($index + 2) . " harus punya stage";
                            $tresshold_rule = false;
                            break;

                        case !collect($value[$index])->has("discount"):
                            $this->messages = "stage " . ($index + 2) . " harus punya dicount";
                            $tresshold_rule = false;
                            break;

                        case !collect($value[$index])->has("product_category"):
                            $this->messages = "stage " . ($index + 2) . " harus punya kategori produk (product_category)";
                            $tresshold_rule = false;
                            break;

                        case !collect($value[$index])->has("sibling_discount"):
                            $this->messages = "stage " . ($index + 2) . " harus punya diskon bersama (sibling_discount)";
                            $tresshold_rule = false;
                            break;

                        default:
                            break;
                    }


                    if (!$tresshold_rule) {
                        return false;
                    }

                    switch (true) {
                        case collect($value[$index])["type"] != "threshold":
                            $this->messages = "stage " . ($index + 1) . " tipe dicount adalah threshold";
                            $tresshold_rule = false;
                            break;

                        case collect($value[$index])["stage"] != $index + 1:
                            $this->messages = "stage adalah " . ($index + 1);
                            $tresshold_rule = false;
                            break;

                        case !is_array($value[0]["discount"]):
                            $this->messages = "stage " . ($index + 1) . " attribute discount harus array";
                            $tresshold_rule = false;
                            break;

                        case !is_array($value[0]["product_category"]):
                            $this->messages = "stage " . ($index + 1) . " attribute product_category harus array";
                            $tresshold_rule = false;
                            break;

                        default:
                            break;
                    }

                    if (!$tresshold_rule) {
                        return false;
                    }
                });

            return $tresshold_rule;
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
}
