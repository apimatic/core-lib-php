<?php

declare(strict_types=1);

namespace Core\Authentication;

use CoreInterfaces\Core\Authentication\AuthInterface;
use CoreInterfaces\Core\Request\ParamInterface;
use CoreInterfaces\Core\Request\RequestSetterInterface;
use CoreInterfaces\Core\Request\TypeValidatorInterface;
use InvalidArgumentException;

/**
 * Use to apply authentication parameters to the request
 */
class CoreAuth implements AuthInterface
{
    private $parameters;
    private $isValid = false;

    /**
     * @param ParamInterface ...$parameters
     */
    public function __construct(...$parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function validate(TypeValidatorInterface $validator): void
    {
        $this->parameters = array_map(function ($param) use ($validator) {
            $param->validate($validator);
            return $param;
        }, $this->parameters);
        $this->isValid = true;
    }

    public function apply(RequestSetterInterface $request): void
    {
        if (!$this->isValid) {
            return;
        }
        $this->parameters = array_map(function ($param) use ($request) {
            $param->apply($request);
            return $param;
        }, $this->parameters);
    }
}
