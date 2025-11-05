<?php

declare(strict_types=1);

namespace Core\Types\Sdk;

class VerificationFailure
{
    private string $errorMessage;

    public function __construct(string $errorMessage)
    {
        $this->errorMessage = $errorMessage;
    }

    public static function init(string $errorMessage): VerificationFailure
    {
        return new VerificationFailure($errorMessage);
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }
}
