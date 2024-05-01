<?php

namespace Core\Logger;

use Core\Utils\CoreHelper;
use Psr\Log\AbstractLogger;

class ConsoleLogger extends AbstractLogger
{
    public function log($level, $message, array $context = []): void
    {
        printf("%s: %s\n", $level, vsprintf($message, array_map(
            function ($key, $value) {
                return CoreHelper::serialize($value);
            },
            $context
        )));
    }
}
