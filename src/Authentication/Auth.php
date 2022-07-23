<?php

declare(strict_types=1);

namespace CoreLib\Authentication;

use CoreDesign\Core\Authentication\AuthGroup;
use CoreDesign\Core\Authentication\AuthInterface;
use CoreDesign\Core\Request\RequestInterface;
use InvalidArgumentException;

class Auth implements AuthInterface
{
    /**
     * @param self|string ...$auths
     */
    public static function and(...$auths): self
    {
        return new self($auths, AuthGroup::AND);
    }

    /**
     * @param self|string ...$auths
     */
    public static function or(...$auths): self
    {
        return new self($auths, AuthGroup::OR);
    }

    /**
     * @var array
     */
    private $auths;

    /**
     * @var array<string,AuthInterface>
     */
    private $authGroups = [];

    /**
     * @var string
     */
    private $groupType;

    /**
     * @param array $auths
     * @param string $groupType
     */
    private function __construct(array $auths, string $groupType)
    {
        $this->auths = $auths;
        $this->groupType = $groupType;
    }

    /**
     * @param array<string,AuthInterface> $authManagers
     */
    public function withAuthManagers(array $authManagers): self
    {
        $this->authGroups = array_map(function ($auth) use ($authManagers) {
            if (is_string($auth)) {
                return $authManagers[$auth];
            } elseif ($auth instanceof Auth) {
                return $auth->withAuthManagers($authManagers);
            }
            return null;
        }, $this->auths);
        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function apply(RequestInterface $request): void
    {
        $success = empty($this->authGroups);
        foreach ($this->authGroups as $authGroup) {
            try {
                $authGroup->apply($request);
                if ($this->groupType == AuthGroup::OR) {
                    $success = true;
                }
            } catch (InvalidArgumentException $e) {
                if ($this->groupType == AuthGroup::AND) {
                    throw $e;
                }
            }
        }
        if (!$success) {
            throw new InvalidArgumentException("Missing required auth credentials");
        }
    }
}
