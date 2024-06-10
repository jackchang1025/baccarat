<?php

namespace App\Baccarat\Service\BettingCalculator;

class BettingCalculatorPair extends BaseBettingCalculator
{

    protected function calculatorRate(int $betAmount): float|int
    {
        $rate = 1;

        while ($rate <= 10){

            if ($this->checkBetAmount($this->format($betAmount * $rate))){
                return $rate;
            }

            $rate += 0.001;
        }

        /**
         * 直接抛出异常无法找到合适的倍率
         */
        throw new \Exception('rate not found');
    }

    public function getOdds(): int
    {
        return 11;
    }
}