<?php

declare(strict_types=1);

namespace Core\TestCase;

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
    private $assertStatusRange = false;
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
        $this->assertStatusRange = true;
        $this->lowerStatusCode = $lowerStatusCode;
        $this->upperStatusCode = $upperStatusCode;
    }

    public function assert(int $statusCode)
    {
        if (isset($this->statusCode)) {
            $this->testCase->assertEquals($this->statusCode, $statusCode, "Status is not $this->statusCode");
            return;
        }
        if (!$this->assertStatusRange) {
            return;
        }
        $message = "Status is not between $this->lowerStatusCode and $this->upperStatusCode";
        $this->testCase->assertGreaterThanOrEqual($this->lowerStatusCode, $statusCode, $message);
        $this->testCase->assertLessThanOrEqual($this->upperStatusCode, $statusCode, $message);
    }
}
