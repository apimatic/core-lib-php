<?php

namespace Core\Tests\Mocking\Authentication;

use Core\Authentication\CoreAuth;
use Core\Request\Parameters\HeaderParam;

class HeaderAuthManager extends CoreAuth
{
    public function __construct($token, $accessToken)
    {
        parent::__construct(
            HeaderParam::init('token', $token)->requiredNonEmpty(),
            HeaderParam::init('authorization', $accessToken)->requiredNonEmpty()
        );
    }
}
