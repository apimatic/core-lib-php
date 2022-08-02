<?php

namespace CoreLib\Core\TestCase\BodyMatchers;

use CoreLib\Core\TestCase\TestHelper;
use CoreLib\Utils\CoreHelper;

class KeysBodyMatcher extends BodyMatcher
{
    public static function init($expectedBody, bool $matchArrayOrder = false, bool $matchArrayCount = false): self
    {
        $matcher = new self($expectedBody, $matchArrayOrder, !$matchArrayCount);
        $matcher->defaultMessage = 'Response body does not match in keys';
        return $matcher;
    }
    protected $checkValues = false;

    public function assert(string $rawBody)
    {
        parent::assert($rawBody);
        if (is_array($this->expectedBody)) {
            $this->testCase->assertTrue(
                TestHelper::isArrayOfJsonObjectsProperSubsetOf(
                    $this->expectedBody,
                    CoreHelper::deserialize($rawBody),
                    $this->allowExtra,
                    $this->matchArrayOrder,
                    $this->checkValues
                ),
                $this->defaultMessage
            );
            return;
        }
        $this->testCase->assertTrue(
            TestHelper::isProperSubsetOf(
                $this->expectedBody,
                CoreHelper::deserialize($rawBody),
                $this->allowExtra,
                $this->matchArrayOrder,
                $this->checkValues
            ),
            $this->defaultMessage
        );
    }
}
