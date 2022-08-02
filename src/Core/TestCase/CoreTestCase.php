<?php

declare(strict_types=1);

namespace CoreLib\Core\TestCase;

use CoreLib\Core\TestCase\BodyMatchers\BodyMatcher;
use CoreLib\Types\CallbackCatcher;
use PHPUnit\Framework\TestCase;

class CoreTestCase
{
    private $callback;
    private $statusCodeMatcher;
    private $headersMatcher;
    private $bodyMatcher;
    public function __construct(TestCase $testCase, CallbackCatcher $callbackCatcher, $result)
    {
        $this->callback = $callbackCatcher;
        $this->statusCodeMatcher = new StatusCodeMatcher($testCase);
        $this->headersMatcher = new HeadersMatcher($testCase);
        $this->bodyMatcher = new BodyMatcher();
        $this->bodyMatcher->shouldAssert = false;
        $this->bodyMatcher->set($testCase, $result);
    }

    public function expectStatus(int $statusCode): self
    {
        $this->statusCodeMatcher->setStatusCode($statusCode);
        return $this;
    }

    public function expectStatusRange(int $lowerStatusCode, int $upperStatusCode): self
    {
        $this->statusCodeMatcher->setStatusRange($lowerStatusCode, $upperStatusCode);
        return $this;
    }

    public function expectHeaders(array $headers): self
    {
        $this->headersMatcher->setHeaders($headers);
        return $this;
    }

    public function allowExtraHeaders(): self
    {
        $this->headersMatcher->allowExtra();
        return $this;
    }

    public function bodyMatcher(BodyMatcher $bodyMatcher): self
    {
        $bodyMatcher->set($this->bodyMatcher->testCase, $this->bodyMatcher->result);
        $this->bodyMatcher = $bodyMatcher;
        return $this;
    }

    public function assert()
    {
        $response = $this->callback->getResponse();
        $this->statusCodeMatcher->assert($response->getStatusCode());
        $this->headersMatcher->assert($response->getHeaders());
        $this->bodyMatcher->assert($response->getRawBody());
    }
}
