<?php

declare(strict_types=1);

namespace Core\Request\Parameters;

use CoreInterfaces\Core\Request\RequestSetterInterface;
use CoreInterfaces\Core\Request\TypeValidatorInterface;
use InvalidArgumentException;

class MultipleParams extends Parameter
{
    /**
     * @var Parameter[]
     */
    protected $parameters;

    protected function __construct(string $typeName)
    {
        parent::__construct('', null, $typeName);
    }
    /**
     * Validates all parameters of the object.
     *
     * @throws InvalidArgumentException
     */
    public function validate(TypeValidatorInterface $validator): void
    {
        $this->parameters = array_map(function ($param) use ($validator) {
            $param->validate($validator);
            return $param;
        }, $this->parameters);
    }

    /**
     * Applies all parameters to the request provided.
     */
    public function apply(RequestSetterInterface $request): void
    {
        $this->parameters = array_map(function ($param) use ($request) {
            $param->apply($request);
            return $param;
        }, $this->parameters);
    }
}
