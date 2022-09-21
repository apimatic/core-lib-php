<?php

declare(strict_types=1);

namespace CoreLib\Core\TestCase\BodyMatchers;

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
        $this->testCase->assertTrue(
            CoreHelper::isProperSubset(
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
