<?php

declare(strict_types=1);

namespace CoreLib\Core\Request\Parameters;

use CoreDesign\Core\Request\RequestSetterInterface;

class HeaderParam extends Parameter
{
    public static function init(string $key, $value): self
    {
        return new self($key, $value);
    }

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
            $request->addHeader($this->key, $this->value);
        }
    }
}
