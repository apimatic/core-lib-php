<?php

declare(strict_types=1);

namespace CoreLib\Utils;

use apimatic\jsonmapper\JsonMapper;
use apimatic\jsonmapper\JsonMapperException;

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

    /**
     * @var JsonMapper
     */
    private $jsonMapper;

    /**
     * @param array<string,string[]> $inheritedModel
     * @param string|null $additionalPropertiesMethodName
     */
    public function __construct(array $inheritedModel, ?string $additionalPropertiesMethodName)
    {
        $this->jsonMapper = new JsonMapper();
        $this->jsonMapper->arChildClasses = $inheritedModel;
        $this->jsonMapper->sAdditionalPropertiesCollectionMethod = $additionalPropertiesMethodName;
    }
}
