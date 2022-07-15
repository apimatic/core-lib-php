<?php

namespace CoreLib\Tests\Core\TestModels;

use CoreLib\Types\DefaultConfigurations;

class MyClient implements DefaultConfigurations
{
    public function getTimeout(): int
    {
        return 20;
    }
}
