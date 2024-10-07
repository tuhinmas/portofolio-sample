<?php
namespace App\Traits;

/**
 * Orion validation before saving data
 */
trait OrionValidationBeforeSave
{
    public function relationshipAssociateCheck($request, $model, $model_id, $attribute_key)
    {

        if (array_key_exists("resources", $request->all())) {
            foreach ($request->resources as $key => $data) {
                if (array_key_exists($attribute_key, $data)) {
                    $model->findOrFail($data[$attribute_key]);
                }
            }
        } else {
            $model->findOrFail($model_id);
        }
    }

    public function relationshipAssociateCheckv2($request, $model, $attribute_key)
    {

        if (array_key_exists("resources", $request->all())) {
            foreach ($request->resources as $key => $data) {
                if (array_key_exists($attribute_key, $data)) {
                    $model->findOrFail($data[$attribute_key]);
                }
            }
        } else {
            if ($request->has($attribute_key)) {
                if (is_array($request->$attribute_key)) {
                    foreach ($request->$attribute_key as $attribute_key) {
                        $model->findOrFail($attribute_key);
                    }
                }
                else {
                    $model->findOrFail($request->$attribute_key);
                }
            }
        }
    }
}
