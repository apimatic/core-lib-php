<?php

namespace CoreLib\Tests\Core;

use CoreLib\Core\CoreConfig;
use CoreLib\Tests\Core\TestModels\MyClient;
use PHPUnit\Framework\TestCase;

class CoreConfigTest extends TestCase
{
    public function testCreateCoreConfigInstance()
    {
        $client = new MyClient();
        $coreConfig = CoreConfig::init($client);
        $this->assertInstanceOf(CoreConfig::class, $coreConfig);
        $this->assertEquals(20, $coreConfig->getDefaultConfig()->getTimeout());
    }
}
