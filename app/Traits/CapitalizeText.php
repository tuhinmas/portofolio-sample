<?php

namespace App\Traits;

trait CapitalizeText
{
    public static function bootCapitalizeText()
    {
        static::saving(function ($model) {
            foreach ($model->getAttributes() as $key => $value) {
                if (is_string($value) && !self::isUuid($value) && !self::isEmail($value) && !in_array($key, self::keyNotIn())) {
                    $model->{$key} = ucwords(strtolower($value));
                }
            }
        });

        static::updating(function ($model) {
            foreach ($model->getAttributes() as $key => $value) {
                if (is_string($value) && !self::isUuid($value) && !self::isEmail($value) && !in_array($key, self::keyNotIn())) {
                    $model->{$key} = ucwords(strtolower($value));
                }
            }
        });
    }

    public static function keyNotIn()
    {
        return [
            "status"
        ];
    }

    public static function isUuid($value)
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value);
    }

    protected static function isEmail($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
}