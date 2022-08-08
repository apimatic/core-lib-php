<?php

namespace CoreLib\Core\Response\Types;

use CoreLib\Core\Response\Context;

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

    public function throw(Context $context)
    {
        throw $context->toApiException($this->description, $this->className);
    }
}
