<?php

declare(strict_types=1);

namespace CoreLib\Utils;

class JsonHelper
{
    /**
     * Check if an array isAssociative (has string keys)
     *
     * @param  array $arr A valid array
     * @return boolean True if the array is Associative, false if it is Indexed
     */
    public static function isAssociative(array $arr): bool
    {
        foreach ($arr as $key => $value) {
            if (is_string($key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Serialize any given mixed value.
     *
     * @param mixed $value Any value to be serialized
     *
     * @return string|null serialized value
     */
    private function serialize($value): ?string
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
     * @param mixed $instance Instance of an object to map the json into
     * @param boolean $isArray Is the Json an object array?
     * @param boolean $allowAdditionalProperties Allow additional properties
     *
     * @return mixed                               Decoded Json
     * @throws \apimatic\jsonmapper\JsonMapperException
     */
    public static function deserialize(
        string $json,
        $instance = null,
        bool $isArray = false,
        bool $allowAdditionalProperties = true
    ) {
        if ($instance == null) {
            return json_decode($json, true);
        } else {
            $mapper = new \apimatic\jsonmapper\JsonMapper();
            if ($allowAdditionalProperties) {
                $mapper->sAdditionalPropertiesCollectionMethod = 'addAdditionalProperty';
            }
            if ($isArray) {
                return $mapper->mapArray(json_decode($json), [], $instance);
            } else {
                return $mapper->map(json_decode($json), $instance);
            }
        }
    }
}
