<?php

namespace App\Baccarat\Service\Rule;

use App\Baccarat\Service\Rule\RuleInterface;

 class LongDragonCanOnlyAroundThreeTimes extends Rule
{

    public function getName(): string
    {
        // TODO: Implement getName() method.
    }

    public function match(string $string): bool
    {
        // TODO: Implement match() method.
    }

    public function getBettingValue(): string
    {
        // TODO: Implement getBettingValue() method.
    }

    public function getPreg(): string
    {
        return '/B{6}P{1,3}$|P{6}B{4}$/';
    }

     public function isMatch(): bool
     {
         // TODO: Implement isMatch() method.
     }
 }