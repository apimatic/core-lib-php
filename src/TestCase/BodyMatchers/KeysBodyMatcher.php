<?php

declare(strict_types=1);

namespace Core\TestCase\BodyMatchers;

use Core\Utils\CoreHelper;

class KeysBodyMatcher extends BodyMatcher
{
    public static function init($expectedBody, bool $matchArrayOrder = false, bool $matchArrayCount = false): self
    {
        $matcher = new self(new BodyComparator(!$matchArrayCount, $matchArrayOrder, false), $expectedBody);
        $matcher->defaultMessage = 'Response body does not match in keys';
        return $matcher;
    }

    public function assert(string $rawBody)
    {
        parent::assert($rawBody);
        $this->testCase->assertTrue(
            $this->bodyComparator->compare($this->expectedBody, CoreHelper::deserialize($rawBody)),
            $this->defaultMessage
        );
    }
}
