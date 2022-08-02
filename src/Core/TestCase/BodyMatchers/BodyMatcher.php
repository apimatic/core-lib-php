<?php

namespace CoreLib\Core\TestCase\BodyMatchers;

use PHPUnit\Framework\TestCase;

class BodyMatcher
{
    protected $expectedBody;
    protected $matchArrayOrder;
    protected $allowExtra;
    protected $defaultMessage = '';
    /**
     * @var TestCase
     */
    public $testCase;
    public $result;
    public $shouldAssert = true;

    public function __construct($expectedBody = null, bool $matchArrayOrder = false, bool $allowExtra = true)
    {
        $this->expectedBody = $expectedBody;
        $this->matchArrayOrder = $matchArrayOrder;
        $this->allowExtra = $allowExtra;
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
