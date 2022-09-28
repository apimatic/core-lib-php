<?php

declare(strict_types=1);

namespace Core\Request\Parameters;

use CoreInterfaces\Core\Request\RequestSetterInterface;

class HeaderParam extends Parameter
{
    /**
     * Initializes a header parameter with the key and value provided.
     */
    public static function init(string $key, $value): self
    {
        return new self($key, $value);
    }

    /**
     * Initializes a header parameter with the value present with the key '$key' in an already collected array '$value'.
     *
     * @param string $key
     * @param mixed $value
     * @param mixed $defaultValue
     */
    public static function initFromCollected(string $key, $value, $defaultValue = null): self
    {
        $instance = self::init($key, $value);
        $instance->pickFromCollected($defaultValue);
        return $instance;
    }

    private function __construct(string $key, $value)
    {
        parent::__construct($key, $value, 'header');
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
            $request->addHeader($this->key, $this->value);
        }
    }
}
