<?php

namespace App\Baccarat\Service\Rule;

abstract class Rule implements RuleInterface
{

    public function __construct(protected string $pattern,protected string $bettingValue)
    {
    }

    public function match(string $string): bool
    {
        return preg_match($this->pattern, $string);
    }

    public function getRule(): string
    {
        return $this->pattern;
    }

    public function getBettingValue(): string
    {
        return $this->bettingValue;
    }

    public function isMatch(): bool
    {
        return true;
    }

    public function __toString(): string
    {
        return "title:{$this->getName()} rule:{$this->getRule()} betting_value:{$this->getBettingValue()}";
    }
}