<?php

declare(strict_types=1);

namespace CoreLib\Authentication;

use CoreDesign\Core\Authentication\AuthInterface;
use CoreDesign\Core\Request\ParamInterface;
use CoreDesign\Core\Request\RequestInterface;

class CoreAuth implements AuthInterface
{
    private $parameters;

    /**
     * @param ParamInterface[] $parameters
     */
    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }

    public function apply(RequestInterface $request): void
    {
        foreach ($this->parameters as $param) {
            $param->apply($request);
        }
    }
}
