<?php

declare(strict_types=1);

namespace Core\Request\Parameters;

use CoreInterfaces\Core\Request\RequestSetterInterface;

class BodyParam extends Parameter
{
    /**
     * Initializes a body parameter with the value specified.
     */
    public static function init($value): self
    {
        return new self('', $value);
    }

    /**
     * Initializes a body parameter with the value present with the key '$key' in an already collected array '$value'.
     *
     * @param string $key
     * @param mixed $value
     * @param mixed $defaultValue
     */
    public static function initFromCollected(string $key, $value, $defaultValue = null): self
    {
        $instance = self::init($value);
        $instance->pickFromCollected($defaultValue, $key);
        return $instance;
    }

    /**
     * Initializes a body parameter with the value and key provided.
     *
     * @param string $key
     * @param mixed $value
     */
    public static function initWrapped(string $key, $value): self
    {
        return new self($key, $value);
    }

    public static function initWrappedFromCollected(string $key, $value, $defaultValue = null): self
    {
        $instance = self::initWrapped($key, $value);
        $instance->pickFromCollected($defaultValue);
        return $instance;
    }

    private function __construct(string $key, $value)
    {
        parent::__construct($key, $value, 'body');
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
     * Adds the parameter to the request provided.
     *
     * @param RequestSetterInterface $request The request to add the parameter to.
     */
    public function apply(RequestSetterInterface $request): void
    {
        if ($this->validated) {
            $request->addBodyParam($this->value, $this->key);
        }
    }
}
