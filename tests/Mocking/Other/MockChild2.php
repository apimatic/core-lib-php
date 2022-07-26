<?php

namespace CoreLib\Tests\Mocking\Other;

class MockChild2 extends MockClass
{
    /**
     * @var string
     */
    public $childBody;

    public function __construct($childBody, ...$body)
    {
        $this->childBody = $childBody;
        parent::__construct($body);
    }

    /**
     * Add a property to this model.
     *
     * @param string $name Name of property
     * @param mixed $value Value of property
     */
    public function addAdditionalProperty(string $name, $value)
    {
        $this->additionalProperties[$name] = $value;
    }
}
