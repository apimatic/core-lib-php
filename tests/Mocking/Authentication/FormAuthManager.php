<?php

namespace CoreLib\Tests\Mocking\Authentication;

use CoreLib\Authentication\CoreAuth;
use CoreLib\Core\Request\Parameters\FormParam;

class FormAuthManager extends CoreAuth
{
    public function __construct($token, $accessToken)
    {
        parent::__construct(
            FormParam::init('token', $token)->required(),
            FormParam::init('authorization', $accessToken)->required()
        );
    }
}
