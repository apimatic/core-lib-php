<?php

namespace CoreLib\Core\TestCase;

use PHPUnit\Framework\TestCase;

class HeadersMatcher
{
    private $headers = [];
    private $allowExtra = false;
    private $testCase;
    public function __construct(TestCase $testCase)
    {
        $this->testCase = $testCase;
    }

    /**
     * Set an array of arrays, where inner arrays must be of length 2,
     * i.e. index0 => headerValue, index1 => checkValueBool
     *
     * @param array<string,array> $headers
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    public function allowExtra(): void
    {
        $this->allowExtra = true;
    }

    public function assert(array $headers)
    {
        if (empty($this->headers)) {
            return;
        }

        // Http headers are case-insensitive
        $expected = array_change_key_case($this->headers);
        $actual = array_change_key_case($headers);
        $message = "Headers do not match";
        if (!$this->allowExtra) {
            $message = "$message strictly";
            $this->testCase->assertCount(count($expected), $actual, $message);
        }

        $actualKeys = array_keys($actual);
        foreach ($expected as $key => $valueArray) {
            $this->testCase->assertTrue(in_array($key, $actualKeys, true), $message);
            if (is_bool($valueArray[1]) && $valueArray[1]) {
                $this->testCase->assertEquals($valueArray[0], $actual[$key], $message);
            }
        }
    }
}
