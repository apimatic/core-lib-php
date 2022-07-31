<?php

declare(strict_types=1);

namespace CoreLib\Tests\Mocking\Types;

use CoreLib\Types\Sdk\CoreContext;

class MockContext extends CoreContext
{
    public function getRequest(): MockRequest
    {
        return $this->request;
    }

    public function getResponse(): MockCoreResponse
    {
        return $this->response;
    }
}
