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

    public static function initWrapped(string $key, $value): self
    {
        return new self($key, $value);
    }

    private function __construct(string $key, $value)
    {
        parent::__construct($key, $value, 'body');
    }

    public function apply(RequestSetterInterface $request): void
    {
        if ($this->validated) {
            $request->addBodyParam($this->value, $this->key);
        }
    }
}
