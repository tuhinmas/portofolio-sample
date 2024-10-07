<?php

namespace Modules\DataAcuan\Rules;

use Illuminate\Contracts\Validation\Rule;
use Modules\DataAcuan\Entities\MarketingAreaDistrict;
use Modules\Personel\Entities\Personel;

class ApplicatorOnMarketingAreaRule implements Rule
{
    protected $messages;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($request, $area_id = null)
    {
        $this->request = $request;
        $this->area_id = $area_id;
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
        $passed = true;
        if (!$this->request->has("resources")) {
            if ($this->area_id) {
                switch (true) {
                    case $this->request->has("personel_id"):
                        $personel = Personel::query()
                            ->where("id", $value)
                            ->where("supervisor_id", $this->request->personel_id)
                            ->first();
                        $passed = $personel ? true : false;
                        break;
                    case !$this->request->has("personel_id"):
                        $applicator = Personel::find($value);

                        $area = MarketingAreaDistrict::query()
                            ->with([
                                "personel",
                            ])
                            ->where("id", $this->$this->area_id["marketing_area_district"])
                            ->first();
                        $passed = $area ? ($applicator->supervisor_id == $area->personel_id) ? true : false : false;
                    default:
                        break;
                }
            }
        } else {
            foreach ($this->request->resources as $area_id => $resource) {
                switch (true) {

                    case collect($resource)->has("personel_id") && collect($resource)->has("applicator_id"):

                        switch (true) {
                            case !$resource["personel_id"]:
                                $passed = false;
                                break;

                            case $resource["applicator_id"]:
                                $personel = Personel::query()
                                    ->where("id", $resource["applicator_id"])
                                    ->where("supervisor_id", $resource["personel_id"])
                                    ->first();

                                $passed = $personel ? true : false;
                                break;

                            default:
                                break;
                        }
                        break;

                    case !collect($resource)->has("personel_id") && collect($resource)->has("applicator_id"):
                        
                        switch (true) {
                            case $resource["applicator_id"]:
                                $applicator = Personel::find($resource["applicator_id"]);
                                $area = MarketingAreaDistrict::query()
                                    ->with([
                                        "personel",
                                    ])
                                    ->where("id", $area_id)
                                    ->first();

                                $passed = $area ? ($applicator->supervisor_id == $area->personel_id) ? true : false : false;
                                break;

                            default:
                                break;
                        }

                    default:
                        break;
                }
            }
        }

        return $passed;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->messages;
        return 'Applicator does not match with marketing, choose another one';
    }
}
