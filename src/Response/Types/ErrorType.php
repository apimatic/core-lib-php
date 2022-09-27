<?php

declare(strict_types=1);

namespace Core\Response\Types;

use Core\Response\Context;

class ErrorType
{
    public static function init(string $description, ?string $className = null): self
    {
        return new self($description, $className);
    }

    private $description;
    private $className;
    private function __construct(string $description, ?string $className)
    {
        $this->description = $description;
        $this->className = $className;
    }

    public function throwable(Context $context)
    {
        return $context->toApiException($this->description, $this->className);
    }
}
