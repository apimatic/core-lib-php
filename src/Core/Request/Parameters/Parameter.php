<?php

declare(strict_types=1);

namespace CoreLib\Core\Request\Parameters;

use Closure;
use CoreDesign\Core\Request\ParamInterface;
use CoreLib\Utils\JsonHelper;
use InvalidArgumentException;
use Throwable;

abstract class Parameter implements ParamInterface
{
    protected $key;
    protected $value;
    protected $validated = false;
    private $valueMissing = false;
    private $serializationError;
    /**
     * @var string|null
     */
    private $paramStrictType;
    private $typeGroupSerializers = [];
    private $typeName;

    public function __construct(string $key, $value, string $typeName)
    {
        $this->key = $key;
        $this->value = $value;
        $this->typeName = $typeName;
    }

    private function getName(): string
    {
        return $this->key == '' ? $this->typeName : $this->key;
    }

    public function required()
    {
        if (is_null($this->value)) {
            $this->valueMissing = true;
        }
    }

    public function serializeBy(callable $serializerMethod)
    {
        try {
            $this->value = Closure::fromCallable($serializerMethod)($this->value);
        } catch (Throwable $e) {
            $this->serializationError = new InvalidArgumentException("Unable to serialize field: " .
                "{$this->getName()}, Due to:\n{$e->getMessage()}");
        }
    }

    /**
     * @param string   $strictType        Strict single type i.e. string, ModelName, etc. or group of types
     *                                    in string format i.e. oneof(...), anyof(...)
     * @param string[] $serializerMethods Methods required for the serialization of specific types in
     *                                    in the provided strict types/type, should be an array in the format:
     *                                    ['path/to/method argumentType', ...]. Default: []
     */
    public function strictType(string $strictType, array $serializerMethods = [])
    {
        $this->paramStrictType = $strictType;
        $this->typeGroupSerializers = $serializerMethods;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function validate(): void
    {
        if ($this->valueMissing) {
            throw new InvalidArgumentException("Missing required $this->typeName field: {$this->getName()}");
        }
        if (isset($this->serializationError)) {
            throw $this->serializationError;
        }
        if (isset($this->paramStrictType)) {
            $this->value = JsonHelper::verifyTypes($this->value, $this->paramStrictType, $this->typeGroupSerializers);
        }
        $this->validated = true;
    }
}
