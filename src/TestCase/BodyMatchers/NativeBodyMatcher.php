<?php

declare(strict_types=1);

namespace Core\TestCase\BodyMatchers;

class NativeBodyMatcher extends BodyMatcher
{
    public static function init($expectedBody, bool $matchArrayOrder = false, bool $matchArrayCount = false): self
    {
        $matcher = new self(new BodyComparator(!$matchArrayCount, $matchArrayOrder, true, true), $expectedBody);
        if (!is_array($expectedBody) && !is_object($expectedBody)) {
            $matcher->defaultMessage = 'Response values does not match';
            return $matcher;
        }
        $array = is_array($expectedBody) ? 'array' : 'object';
        $order = $matchArrayOrder ? ' order' : '';
        $size = $matchArrayCount ? ' size' : '';
        $in = ($matchArrayOrder || $matchArrayCount) ? ' in' : '';
        $or = ($matchArrayOrder && $matchArrayCount) ? ' or' : '';
        $matcher->defaultMessage = "Response $array values does not match$in$order$or$size";
        return $matcher;
    }

    public function assert(string $rawBody)
    {
        parent::assert($rawBody);
        $this->testCase->assertTrue(
            $this->bodyComparator->compare($this->expectedBody, $this->result),
            $this->defaultMessage
        );
    }
}
