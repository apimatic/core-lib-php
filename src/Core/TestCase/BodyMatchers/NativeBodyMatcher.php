<?php

namespace CoreLib\Core\TestCase\BodyMatchers;

use CoreLib\Utils\CoreHelper;

class NativeBodyMatcher extends BodyMatcher
{
    public static function init($expectedBody, bool $matchArrayOrder = false, bool $matchArrayCount = false): self
    {
        $matcher = new self($expectedBody, $matchArrayOrder, !$matchArrayCount);
        $matcher->defaultMessage = 'Response does not match expected value';
        return $matcher;
    }

    public function assert(string $rawBody)
    {
        parent::assert($rawBody);
        if (!is_array($this->expectedBody)) {
            $this->testCase->assertEquals($this->expectedBody, $this->result, $this->defaultMessage);
            return;
        }
        if ($this->matchArrayOrder) {
            if ($this->allowExtra) {
                $this->defaultMessage = 'Response array values does not match in order';
            } else {
                $this->defaultMessage = 'Response array values does not match in order or size';
            }
        } else {
            if ($this->allowExtra) {
                $this->defaultMessage = 'Response array values does not match';
            } else {
                $this->defaultMessage = 'Response array values does not match in size';
            }
        }
        $this->testCase->assertTrue(
            CoreHelper::equals($this->expectedBody, $this->result, $this->allowExtra, $this->matchArrayOrder),
            $this->defaultMessage
        );
    }
}
