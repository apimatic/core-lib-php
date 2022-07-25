<?php

namespace CoreLib\Tests\Mocking\Other;

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
