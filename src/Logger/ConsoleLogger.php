<?php

namespace Core\Logger;

use Closure;
use Core\Utils\CoreHelper;
use Psr\Log\AbstractLogger;

class ConsoleLogger extends AbstractLogger
{
    /**
     * A callable function that takes in a format and any number of parameters to satisfy that format.
     * For example: "printf", will be called like printf('%s %s', 'a', 'b')
     *
     * @var callable
     */
    private $printer;

    public function __construct(callable $printer)
    {
        $this->printer = $printer;
    }

    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = []): void
    {
        Closure::fromCallable($this->printer)("%s: %s\n", $level, str_replace(
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
