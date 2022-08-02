<?php

namespace CoreLib\Core\TestCase;

use PHPUnit\Framework\TestCase;

class StatusCodeMatcher
{
    /**
     * @var int|null
     */
    private $statusCode;

    /**
     * @var int|null
     */
    private $lowerStatusCode;

    /**
     * @var int|null
     */
    private $upperStatusCode;

    private $testCase;
    public function __construct(TestCase $testCase)
    {
        $this->testCase = $testCase;
    }

    public function setStatusCode(int $statusCode): void
    {
        $this->statusCode = $statusCode;
    }

    public function setStatusRange(int $lowerStatusCode, int $upperStatusCode): void
    {
        $this->lowerStatusCode = $lowerStatusCode;
        $this->upperStatusCode = $upperStatusCode;
    }

    public function assert(int $statusCode)
    {
        if (isset($this->statusCode)) {
            $this->testCase->assertEquals($this->statusCode, $statusCode, "Status is not $this->statusCode");
        } elseif (isset($this->lowerStatusCode, $this->upperStatusCode)) {
            $message = "Status is not between $this->lowerStatusCode and $this->upperStatusCode";
            $this->testCase->assertGreaterThanOrEqual($this->statusCode, $this->lowerStatusCode, $message);
            $this->testCase->assertLessThanOrEqual($this->statusCode, $this->upperStatusCode, $message);
        }
    }
}
