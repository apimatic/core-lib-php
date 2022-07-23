<?php

namespace CoreLib\Tests\Mocking;

class MockClass
{
    /**
     * @var array
     */
    public $body;

    /**
     * @param mixed ...$body
     */
    public function __construct(...$body)
    {
        $this->body = $body;
    }
}
