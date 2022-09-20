<?php

namespace CoreLib\Core\TestCase\BodyMatchers;

use CoreLib\Utils\CoreHelper;

class NativeBodyMatcher extends BodyMatcher
{
    public static function init($expectedBody, bool $matchArrayOrder = false, bool $matchArrayCount = false): self
    {
        return new self($expectedBody, $matchArrayOrder, !$matchArrayCount);
    }

    public function assert(string $rawBody)
    {
        parent::assert($rawBody);
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
        $left = $this->expectedBody;
        $right = $this->result;
        if (
            $this->allowExtra && is_array($left) && !CoreHelper::isAssociative($left)
            && is_array($right) && count($left) > count($right)
        ) {
            // Special Array case for Native:
            // replacing left with right, as left array has more
            // elements and can not be proper subset of right array
            $left = $right;
            $right = $this->expectedBody;
        }
        $this->testCase->assertTrue(
            CoreHelper::isProperSubset($left, $right, $this->allowExtra, $this->matchArrayOrder),
            $this->defaultMessage
        );
    }
}
