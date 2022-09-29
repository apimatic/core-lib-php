<?php

declare(strict_types=1);

namespace Core\Request\Parameters;

use CoreInterfaces\Core\Request\RequestSetterInterface;

class HeaderParam extends Parameter
{
    public static function init(string $key, $value): self
    {
        return new self($key, $value);
    }

    private function __construct(string $key, $value)
    {
        parent::__construct($key, $value, 'header');
    }

    public function apply(RequestSetterInterface $request): void
    {
        if ($this->validated) {
            $request->addHeader($this->key, $this->value);
        }
    }
}
