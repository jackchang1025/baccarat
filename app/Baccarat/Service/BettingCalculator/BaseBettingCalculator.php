<?php

namespace App\Baccarat\Service\BettingCalculator;

use Hyperf\Collection\Collection;

abstract class BaseBettingCalculator
{
    protected int $totalBetAmount = 0;

    protected int $currentBetAmount = 0;

    public function __construct(
        protected readonly int $min = 20,
        protected readonly int $max = 9999,
        protected readonly int $expectedProfit = 100,
        protected readonly int $rounds = 40
    )
    {
        $this->currentBetAmount = $this->min;
    }

    abstract protected function calculatorRate(int $betAmount): float|int;

    abstract public function getOdds(): int;

    protected function checkBetAmount(int $betAmount): bool
    {
        $condition1 = $betAmount >= $this->min && $betAmount <= $this->max;
        $condition2 = $this->getWinAmount($betAmount) - ($this->getTotalBetAmount() + $betAmount) >= $this->expectedProfit;

//        echo "betAmount:{$betAmount} TotalBetAmount:". $this->getTotalBetAmount() + $betAmount ."WinTotalAmount:{$this->getWinAmount($betAmount)} WinAmount:".$this->getWinAmount($betAmount) - ($this->getTotalBetAmount() + $betAmount)." expectedProfit:{$this->expectedProfit}".PHP_EOL;
        return $condition1 && $condition2;
    }

    public function getCurrentBetAmount(): int
    {
        return $this->currentBetAmount;
    }

    public function setCurrentBetAmount(int $currentBetAmount): void
    {
        $this->currentBetAmount = $currentBetAmount;
    }

    public function getTotalBetAmount(): int
    {
        return $this->totalBetAmount;
    }

    /**
     * 自增 当前下注总金额
     * @param int $betAmount
     * @return void
     */
    public function incrTotalBetAmount(int $betAmount): void
    {
        $this->totalBetAmount += $betAmount;
    }

    protected function calculatorBetAmount(int $betAmount, float $rate): int
    {
        $betAmount = $this->format($betAmount * $rate);

        if (!$this->checkBetAmount($betAmount)) {
            throw new \Exception('Invalid bet amount');
        }

        return $betAmount;
    }

    public function handle(): Collection
    {

        $bettingLog = new Collection();

        for ($i = 1; $i <= $this->rounds; $i++) {

            // 获取递增公比数
            $rate = $this->calculatorRate($this->getCurrentBetAmount());

            // 根据公比数计算当前下注金额
            $betAmount = $this->calculatorBetAmount($this->getCurrentBetAmount(), $rate);

            $this->setCurrentBetAmount($betAmount);

            $this->incrTotalBetAmount($betAmount);

            $totalWinAmount = $this->getWinAmount($betAmount);

            $bettingLog->push(new BaccaratLog(issue: $i, increaseRate: $rate, betAmount: $this->getCurrentBetAmount(), totalAmount: $this->getTotalBetAmount(), winAmount: $totalWinAmount - $this->getTotalBetAmount(), winTotalAmount: $totalWinAmount));
        }

        return $bettingLog;
    }

    protected function format($value): int
    {
        return floor($value);
    }

    protected function getWinAmount(int $betAmount): int
    {
        return $this->format($betAmount * $this->getOdds());
    }
}