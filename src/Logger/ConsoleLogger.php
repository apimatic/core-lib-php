<?php

namespace Core\Logger;

use Core\Utils\CoreHelper;
use Psr\Log\AbstractLogger;

class ConsoleLogger extends AbstractLogger
{
    public function log($level, $message, array $context = []): void
    {
        printf("%s: %s\n", $level, str_replace(
            array_map(function ($key) {
                return '{' . $key . '}';
            }, array_keys($context)),
            array_map(function ($value) {
                return CoreHelper::serialize($value);
            }, $context),
            $message
        ));
    }
}
