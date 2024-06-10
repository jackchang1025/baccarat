<?php

namespace App\Baccarat\Service\BettingCalculator;

use Hyperf\Collection\Collection;

class BaccaratTiePairs
{
    protected Collection $bettingLog;

    protected static array $types = [
        'tie' => 8,
        'pair' => 11
    ];

    public function __construct(
        protected string $type,
        protected int $firstBet = 2,
        protected int $rounds = 60,
    ) {
        if (!array_key_exists($this->type, self::$types)) {
            throw new \InvalidArgumentException('Invalid type');
        }
        $this->bettingLog = new Collection();
    }

    private function f(float $r, int $n, float $p): float
    {
        return 11 * $r ** ($n - 1) - (1 - $r ** $n) / (1 - $r) - $p / 2;
    }

    private function bisect(int $n, float $p, float $a = 1.0, float $b = 1.2, float $tol = 1e-8): float
    {
        while ($b - $a > $tol) {
            $c = ($a + $b) / 2;
            if ($this->f($c, $n, $p) < 0) {
                $a = $c;
            } else {
                $b = $c;
            }
        }
        return ($a + $b) / 2;
    }

    public function run(): Collection
    {
        $totalBet = 0;
        $bettingLog = new Collection();
        $rate = $this->bisect($this->rounds, $this->firstBet);

        for ($i = 1; $i <= $this->rounds; $i++) {
            $bet = intval($this->firstBet * $rate ** ($i - 1));
            $totalBet += $bet;
            $winTotalAmount = $this->calculatorAmount($bet, $this->getMultiplier());
            $bettingLog->push(new BaccaratLog(
                issue: $i,
                increaseRate: $rate,
                betAmount: $bet,
                totalAmount: $totalBet,
                winAmount: $winTotalAmount - $totalBet,
                winTotalAmount: $winTotalAmount
            ));
        }

        return $bettingLog;
    }

    public function calculatorAmount(float $bet, float $rate): int
    {
        return intval($bet * $rate);
    }

    public function getMultiplier(): int
    {
        return self::$types[$this->type];
    }
}