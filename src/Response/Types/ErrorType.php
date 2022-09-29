<?php

declare(strict_types=1);

namespace Core\Response\Types;

use Core\Response\Context;

class ErrorType
{
    /**
     * Initializes a new object with the description and class name provided.
     */
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

    /**
     * Throws an Api exception from the context provided.
     */
    public function throwable(Context $context)
    {
        return $context->toApiException($this->description, $this->className);
    }
}
