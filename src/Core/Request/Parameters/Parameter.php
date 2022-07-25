<?php

declare(strict_types=1);

namespace CoreLib\Core\Request\Parameters;

use CoreDesign\Core\Request\ParamInterface;
use InvalidArgumentException;

abstract class Parameter implements ParamInterface
{
    protected $key;
    protected $value;
    private $valueMissing = false;
    private $typeName;

    public function __construct(string $key, $value, string $typeName)
    {
        $this->key = $key;
        $this->value = $value;
        $this->typeName = $typeName;
    }

    public function required()
    {
        if (is_null($this->value)) {
            $this->valueMissing = true;
        }
    }

    public function serializeBy(callable $serializerMethod)
    {
        // TODO: Implement serializeBy() method.
    }

    public function typeGroup(string $typeGroup, array $serializerMethods = [])
    {
        // TODO: Implement typeGroup() method.
    }

    /**
     * @throws InvalidArgumentException
     */
    public function validate(): void
    {
        if ($this->valueMissing) {
            throw new InvalidArgumentException("Missing required $this->typeName field: $this->key");
        }
    }
}
