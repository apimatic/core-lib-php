<?php

declare(strict_types=1);

namespace CoreLib\Core\Response;

class ResponseHandler
{
    public function validate(Context $context)
    {
        // return validated response in specified type
        return $context->getResponse()->getBody();
    }
}
