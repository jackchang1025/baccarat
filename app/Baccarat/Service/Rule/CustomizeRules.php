<?php

namespace App\Baccarat\Service\Rule;


class CustomizeRules extends Rule
{
    public function __construct(protected string $pattern,protected string $bettingValue,protected string $name)
    {
        parent::__construct($pattern,$bettingValue);
    }

    public function getName(): string
    {
        return $this->name;
    }
}