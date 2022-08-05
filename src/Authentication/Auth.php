<?php

declare(strict_types=1);

namespace CoreLib\Authentication;

use CoreDesign\Core\Authentication\AuthGroup;
use CoreDesign\Core\Authentication\AuthInterface;
use CoreDesign\Core\Request\RequestSetterInterface;
use CoreDesign\Core\Request\TypeValidatorInterface;
use InvalidArgumentException;

/**
 * Use to group multiple Auth schemes with either `AND` or `OR`
 */
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
    private $isValid = false;

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
            if (is_string($auth) && isset($authManagers[$auth])) {
                return $authManagers[$auth];
            } elseif ($auth instanceof Auth) {
                return $auth->withAuthManagers($authManagers);
            }
            throw new InvalidArgumentException("AuthManager not found with name: " . json_encode($auth));
        }, $this->auths);
        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function validate(TypeValidatorInterface $validator): void
    {
        $success = empty($this->authGroups);
        $errors = '';
        foreach ($this->authGroups as $authGroup) {
            try {
                $authGroup->validate($validator);
                if ($this->groupType == AuthGroup::OR) {
                    $success = true;
                }
            } catch (InvalidArgumentException $e) {
                if ($this->groupType == AuthGroup::AND) {
                    throw $e;
                }
                $errors .= "\n-> {$e->getMessage()}";
            }
        }
        if ($this->groupType == AuthGroup::OR && !$success) {
            throw new InvalidArgumentException("Missing required auth credentials:$errors");
        }
        $this->isValid = true;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function apply(RequestSetterInterface $request): void
    {
        if (!$this->isValid) {
            return;
        }
        foreach ($this->authGroups as $authGroup) {
            $authGroup->apply($request);
        }
    }
}
