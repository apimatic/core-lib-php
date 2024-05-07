<?php

namespace Core\Tests\Mocking\Logger;

interface MockPrinter
{
    public function printMessage($format, $level, $message);
}
