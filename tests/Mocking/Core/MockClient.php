<?php

namespace CoreLib\Tests\Mocking\Core;

use CoreDesign\Core\Request\RequestMethod;
use CoreDesign\Http\HttpConfigurations;

class MockClient implements HttpConfigurations
{
    public function getTimeout(): int
    {
        return 20;
    }

    public function shouldEnableRetries(): bool
    {
        return true;
    }

    public function getNumberOfRetries(): int
    {
        return 2;
    }

    public function getRetryInterval(): float
    {
        return 2.0;
    }

    public function getBackOffFactor(): float
    {
        return 2.0;
    }

    public function getMaximumRetryWaitTime(): int
    {
        return 120;
    }

    public function shouldRetryOnTimeout(): bool
    {
        return true;
    }

    public function getHttpStatusCodesToRetry(): array
    {
        return [500, 501];
    }

    public function getHttpMethodsToRetry(): array
    {
        return [RequestMethod::GET, RequestMethod::PUT];
    }
}
