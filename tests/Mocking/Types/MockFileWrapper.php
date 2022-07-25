<?php

namespace CoreLib\Tests\Mocking\Types;

use CoreLib\Types\Sdk\CoreFileWrapper;

class MockFileWrapper extends CoreFileWrapper
{
    public static function createFromPath(string $realFilePath, ?string $mimeType = null, ?string $filename = ''): self
    {
        return new self($realFilePath, $mimeType, $filename);
    }

    private function __construct(string $realFilePath, ?string $mimeType, ?string $filename)
    {
        parent::__construct($realFilePath, $mimeType, $filename);
    }
}
