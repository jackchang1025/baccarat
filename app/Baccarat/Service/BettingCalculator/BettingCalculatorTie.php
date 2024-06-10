<?php

namespace App\Baccarat\Service\BettingCalculator;

class BettingCalculatorTie extends BaseBettingCalculator
{
    public function getOdds(): int
    {
        return 8;
    }

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

    protected function calculatorRates(int $betAmount): float
    {
        $left = 1;
        $right = 2;
        $epsilon = 0.0001;

        while ($right - $left > $epsilon) {
            $mid = ($left + $right) / 2;
            if ($this->checkBetAmount($this->format($betAmount * $mid))) {
                $right = $mid;
            } else {
                $left = $mid;
            }
        }

        return $left;
    }
}