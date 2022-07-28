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

    public function apply(RequestSetterInterface $request): void
    {
        if (!$this->validated) {
            return;
        }
        if (is_object($this->value)) {
            $this->value = (array) $this->value;
        }
        if (is_null($this->value)) {
            $replaceValue = '';
        } elseif (is_array($this->value)) {
            $val = array_map('strval', $this->value);
            $val = $this->encode ? array_map('urlencode', $val) : $val;
            $replaceValue = implode("/", $val);
        } else {
            $val = strval($this->value);
            $replaceValue = $this->encode ? urlencode($val) : $val;
        }
        $request->addTemplate($this->key, $replaceValue);
    }
}
