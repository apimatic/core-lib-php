<?php

namespace CoreLib\Utils;

class CoreHelper
{
    /**
     * Serialize any given mixed value.
     *
     * @param mixed $value Any value to be serialized
     *
     * @return string|null serialized value
     */
    public static function serialize($value): ?string
    {
        if (is_string($value) || is_null($value)) {
            return $value;
        }
        return json_encode($value);
    }

    /**
     * Deserialize a Json string
     *
     * @param string $json A valid Json string
     *
     * @return mixed Decoded Json
     */
    public static function deserialize(string $json)
    {
        return json_decode($json, true);
    }
}
