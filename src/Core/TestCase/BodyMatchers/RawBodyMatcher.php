<?php

namespace CoreLib\Core\TestCase\BodyMatchers;

use CoreLib\Types\Sdk\CoreFileWrapper;
use CoreLib\Utils\CoreHelper;

class RawBodyMatcher extends BodyMatcher
{
    public static function init($expectedBody): self
    {
        $matcher = new self($expectedBody);
        $matcher->defaultMessage = 'Response body does not match exactly';
        return $matcher;
    }

    public function assert(string $rawBody)
    {
        parent::assert($rawBody);
        if ($this->expectedBody instanceof CoreFileWrapper) {
            $this->expectedBody = CoreHelper::serialize($this->expectedBody);
            $this->defaultMessage = 'Binary result does not match the given file';
        }
        $this->testCase->assertEquals($this->expectedBody, $rawBody, $this->defaultMessage);
    }
}
