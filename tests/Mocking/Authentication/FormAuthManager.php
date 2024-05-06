<?php

namespace Core\Tests\Mocking\Authentication;

use Core\Authentication\CoreAuth;
use Core\Request\Parameters\FormParam;

class FormAuthManager extends CoreAuth
{
    public function __construct($token, $accessToken)
    {
        parent::__construct(
            FormParam::init('token', $token)->requiredNonEmpty(),
            FormParam::init('authorization', $accessToken)->requiredNonEmpty()
        );
    }
}
