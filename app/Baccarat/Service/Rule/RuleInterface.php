<?php

namespace App\Baccarat\Service\Rule;

interface RuleInterface
{
    public function getName(): string;

    public function match(string $string): bool;

    public function getBettingValue(): string;

    public function isMatch(): bool;

    public function getRule(): string;

    public function __toString(): string;
}