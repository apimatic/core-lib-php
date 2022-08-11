<?php

declare(strict_types=1);

namespace CoreLib\Core\Request\Parameters;

use CoreDesign\Core\Request\RequestSetterInterface;

class TemplateParam extends Parameter
{
    public static function init(string $key, $value): self
    {
        return new self($key, $value);
    }

    public static function initFromCollected(string $key, $value, $defaultValue = null): self
    {
        $instance = self::init($key, $value);
        $instance->pickFromCollected($defaultValue);
        return $instance;
    }

    private $encode = true;
    private function __construct(string $key, $value)
    {
        parent::__construct($key, $value, 'template');
    }

    public function required(): self
    {
        parent::required();
        return $this;
    }

    public function serializeBy(callable $serializerMethod): self
    {
        parent::serializeBy($serializerMethod);
        return $this;
    }

    public function strictType(string $strictType, array $serializerMethods = []): self
    {
        parent::strictType($strictType, $serializerMethods);
        return $this;
    }

    public function dontEncode(): self
    {
        $this->encode = false;
        return $this;
    }

    private function getReplacerValue($value): string
    {
        if (is_object($value)) {
            $value = (array) $value;
        }
        if (is_bool($value)) {
            $value = var_export($value, true);
        }
        if (is_null($value)) {
            return '';
        } elseif (is_array($value)) {
            $val = array_map([$this, 'getReplacerValue'], $value);
            return implode("/", $val);
        }
        $val = strval($value);
        return $this->encode ? urlencode($val) : $val;
    }

    public function apply(RequestSetterInterface $request): void
    {
        if ($this->validated) {
            $request->addTemplate($this->key, $this->getReplacerValue($this->value));
        }
    }
}
