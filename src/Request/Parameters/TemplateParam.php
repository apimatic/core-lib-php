<?php

declare(strict_types=1);

namespace Core\Request\Parameters;

use CoreInterfaces\Core\Request\RequestSetterInterface;

class TemplateParam extends Parameter
{
    /**
     * Initializes a template parameter with the key and value provided.
     */
    public static function init(string $key, $value): self
    {
        return new self($key, $value);
    }

    /**
     * Initializes a template parameter with the value present with the key '$key'
     * in an already collected array '$value'.
     */
    public static function initFromCollected(string $key, $value, $defaultValue = null): self
    {
        $instance = self::init($key, $value);
        $instance->pickFromCollected($defaultValue);
        return $instance;
    }

    private $encode = true;
    private function __construct(string $key, $value)
    {
        parent::__construct($key, $value, 'template');
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
     * Disables http encoding for the parameter.
     */
    public function dontEncode(): self
    {
        $this->encode = false;
        return $this;
    }

    private function getReplacerValue($value): string
    {
        if (is_object($value)) {
            $value = (array) $value;
        }
        if (is_bool($value)) {
            $value = var_export($value, true);
        }
        if (is_null($value)) {
            return '';
        } elseif (is_array($value)) {
            $val = array_map([$this, 'getReplacerValue'], $value);
            return implode("/", $val);
        }
        $val = strval($value);
        return $this->encode ? urlencode($val) : $val;
    }

    /**
     * Adds the parameter to the request provided.
     *
     * @param RequestSetterInterface $request The request to add the parameter to.
     */
    public function apply(RequestSetterInterface $request): void
    {
        if ($this->validated) {
            $request->addTemplate($this->key, $this->getReplacerValue($this->value));
        }
    }
}
