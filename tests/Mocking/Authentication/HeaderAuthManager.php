<?php

namespace CoreLib\Tests\Mocking\Authentication;

use CoreLib\Authentication\CoreAuth;
use CoreLib\Core\Request\Parameters\HeaderParam;

class HeaderAuthManager extends CoreAuth
{
    public function __construct($token, $accessToken)
    {
        parent::__construct(
            HeaderParam::init('token', $token)->required(),
            HeaderParam::init('authorization', $accessToken)->required()
        );
    }
}
