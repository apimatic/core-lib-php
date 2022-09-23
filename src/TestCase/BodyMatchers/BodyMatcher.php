<?php

declare(strict_types=1);

namespace Core\TestCase\BodyMatchers;

use PHPUnit\Framework\TestCase;

class BodyMatcher
{
    protected $expectedBody;
    protected $bodyComparator;
    protected $defaultMessage = '';
    /**
     * @var TestCase
     */
    public $testCase;
    public $result;
    public $shouldAssert = true;

    public function __construct(BodyComparator $bodyComparator, $expectedBody = null)
    {
        $this->bodyComparator = $bodyComparator;
        $this->expectedBody = $expectedBody;
    }

    public function set(TestCase $testCase, $result)
    {
        $this->testCase = $testCase;
        $this->result = $result;
    }

    public function assert(string $rawBody)
    {
        if ($this->shouldAssert) {
            $this->testCase->assertNotNull($this->result, 'Result does not exist');
        }
    }
}
