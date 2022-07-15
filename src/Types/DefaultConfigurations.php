<?php

namespace CoreLib\Types;

interface DefaultConfigurations
{
    /**
     * Get timeout for API calls in seconds.
     */
    public function getTimeout(): int;
}
