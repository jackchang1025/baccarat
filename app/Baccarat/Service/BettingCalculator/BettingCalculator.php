<?php

namespace App\Baccarat\Service\BettingCalculator;

class BettingCalculator extends BaseBettingCalculator
{
    public function calculate(): array
    {
        $m = $this->min;
        $f = 1;

        while ($m <= $this->max) {
            $t = $this->calculateTotalBet($m, $f);

            if ($this->isConditionMet($m, $f, $t)) {
                return ['M' => $m, 'F' => $f];
            }

            $f += 0.001;
        }

        return [];
    }

    public function calculateF(int $betAmount): float
    {
        $left = 1;
        $right = 2;
        $epsilon = 0.0001;

        while ($right - $left > $epsilon) {
            $mid = ($left + $right) / 2;
            $t = $this->calculateTotalBet($betAmount, $mid);

            if ($this->isConditionMet($betAmount, $mid, $t)) {
                $right = $mid;
            } else {
                $left = $mid;
            }
        }

        return round($left, 4);
    }

    private function calculateTotalBet($m, $f): float
    {
        $t = 0;

        for ($i = 0; $i < $this->rounds; $i++) {
            $t += intval($m * pow($f, $i));
        }

        return $t;
    }

    protected function isConditionMet($m, $f, $t): bool
    {
        $condition1 = intval($m * $f) > $t;
        $condition2 = abs(intval($m * $f) - $t - $this->expectedProfit) <= 1;

        return $condition1 && $condition2;
    }

    public function getOdds(): int
    {
        return 8;
    }

    protected function calculatorAmount(): int
    {
        return $this->min;
    }

    protected function calculatorRate(int $betAmount): float|int
    {
        $rate = 1;

        while (true){

            if ($this->checkRate($betAmount,$rate)){
                return $rate;
            }

            $rate += 0.001;
        }
    }

    protected function checkRate(int $betAmount,float $rate): bool
    {
        return $this->format($betAmount * $rate * $this->getOdds()) - $this->totalBetAmount >= $this->expectedProfit;
    }
}