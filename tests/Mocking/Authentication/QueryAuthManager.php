<?php

namespace CoreLib\Tests\Mocking\Authentication;

use CoreLib\Authentication\CoreAuth;
use CoreLib\Core\Request\Parameters\QueryParam;

class QueryAuthManager extends CoreAuth
{
    public function __construct($token, $accessToken)
    {
        parent::__construct(
            QueryParam::init('token', $token)->required(),
            QueryParam::init('authorization', $accessToken)->required()
        );
    }
}
