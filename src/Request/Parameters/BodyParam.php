<?php

declare(strict_types=1);

namespace Core\Request\Parameters;

use CoreInterfaces\Core\Request\RequestSetterInterface;

class BodyParam extends Parameter
{
    public static function init($value): self
    {
        return new self('', $value);
    }

    public static function initFromCollected(string $key, $value, $defaultValue = null): self
    {
        $instance = self::init($value);
        $instance->pickFromCollected($defaultValue, $key);
        return $instance;
    }

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

    public function required(): self
    {
        parent::required();
        return $this;
    }

    public function serializeBy(callable $serializerMethod): self
    {
        parent::serializeBy($serializerMethod);
        return $this;
    }

    public function strictType(string $strictType, array $serializerMethods = []): self
    {
        parent::strictType($strictType, $serializerMethods);
        return $this;
    }

    public function apply(RequestSetterInterface $request): void
    {
        if ($this->validated) {
            $request->addBodyParam($this->value, $this->key);
        }
    }
}
