<?php

declare(strict_types=1);

namespace CoreLib\Tests\Mocking\Types;

use CoreLib\Types\Sdk\CoreFileWrapper;

class MockFileWrapper extends CoreFileWrapper
{
    public static function createFromPath(string $realFilePath, ?string $mimeType = null, ?string $filename = ''): self
    {
        return new self($realFilePath, $mimeType, $filename);
    }
}
