<?php

namespace CoreLib\Tests\Mocking\Other;

use Exception;

class MockException2 extends Exception implements \Throwable
{
    public $reason;
    public $request;
    public $response;

    /**
     * @var MockClass
     */
    public $other2;
}
