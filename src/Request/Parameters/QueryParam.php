<?php

declare(strict_types=1);

namespace Core\Request\Parameters;

use CoreInterfaces\Core\Request\RequestArraySerialization;
use CoreInterfaces\Core\Request\RequestSetterInterface;

class QueryParam extends EncodedParam
{
    /**
     * Initializes a query parameter with the key and value provided.
     */
    public static function init(string $key, $value): self
    {
        return new self($key, $value);
    }

    /**
     * Initializes a query parameter with the value present with the key '$key' in an already collected array '$value'.
     */
    public static function initFromCollected(string $key, $value, $defaultValue = null): self
    {
        $instance = self::init($key, $value);
        $instance->pickFromCollected($defaultValue);
        return $instance;
    }

    private function __construct(string $key, $value)
    {
        parent::__construct($key, $value, 'query');
    }

    /**
     * Marks the value of the parameter as required and throws an exception on validate if the value is missing.
     */
    public function required(): self
    {
        parent::required();
        return $this;
    }

    /**
     * Serializes the parameter using the method provided.
     *
     * @param callable $serializerMethod The method to use for serialization.
     */
    public function serializeBy(callable $serializerMethod): self
    {
        parent::serializeBy($serializerMethod);
        return $this;
    }

    /**
     * Enables strict type checking for parameter value.
     */
    public function strictType(string $strictType, array $serializerMethods = []): self
    {
        parent::strictType($strictType, $serializerMethods);
        return $this;
    }

    /**
     * Sets the parameter format to un-indexed.
     */
    public function unIndexed(): self
    {
        $this->format = RequestArraySerialization::UN_INDEXED;
        return $this;
    }

    /**
     * Sets the parameter format to plain.
     */
    public function plain(): self
    {
        $this->format = RequestArraySerialization::PLAIN;
        return $this;
    }

    /**
     * Sets the parameter format to comma separated.
     */
    public function commaSeparated(): self
    {
        $this->format = RequestArraySerialization::CSV;
        return $this;
    }

    /**
     * Sets the parameter format to tab separated.
     */
    public function tabSeparated(): self
    {
        $this->format = RequestArraySerialization::TSV;
        return $this;
    }

    /**
     * Sets the parameter format to pipe separated.
     */
    public function pipeSeparated(): self
    {
        $this->format = RequestArraySerialization::PSV;
        return $this;
    }

    /**
     * Adds the parameter to the request provided.
     *
     * @param RequestSetterInterface $request The request to add the parameter to.
     */
    public function apply(RequestSetterInterface $request): void
    {
        if (!$this->validated) {
            return;
        }
        $value = $this->prepareValue($this->value);
        $query = $this->httpBuildQuery([$this->key => $value], $this->format);
        if (empty($query)) {
            return;
        }
        $hasParams = (strrpos($request->getQueryUrl(), '?') > 0);
        $separator = (($hasParams) ? '&' : '?');
        $request->appendPath($separator . $query);
    }
}
