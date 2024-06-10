<?php

namespace App\Baccarat\Service\BettingCalculator;

use Hyperf\Collection\Collection;

class BaccaratTiePair
{
    // 定义常量
    const MAX_BETS = 60;  // 最大投注次数

    const Q = 1.00116;  // 等比数列的公比

    protected Collection $bettingLog;

    protected static array $types = [
        'tie'=>8,
        'pair' => 11
    ];

    public function __construct(
        protected string $type,
        protected int $firstBet = 2,
        protected int $rounds = 60,
        protected array $increaseRates = [1,1.1,1.2,1.3,1.4,1.5,1.6,1.7,1.8,1.9,2]
    )
    {
        if (!array_key_exists($this->type, self::$types)) {
            throw new \InvalidArgumentException('Invalid type');
        }
        $this->bettingLog = new Collection();
    }

    public function run(): Collection
    {
        foreach ($this->increaseRates as $rate) {
            if ($rate < 1){
                throw new \InvalidArgumentException('Invalid rate');
            }
            $this->bettingLog->push($this->calculate($rate));
        }

        return $this->bettingLog;
    }

    protected function calculate(float $rate): Collection
    {
        $bet = $this->firstBet;
        $totalBet = 0;
        $bettingLog = new Collection();

        for ($i = 1; $i <= $this->rounds; $i++) {
            $totalBet += $bet;

            $totalAmount = $this->calculatorAmount($bet , $this->getMultiplier());
            $bettingLog->push(new BaccaratLog(issue: $i, increaseRate: $rate, betAmount: $bet, totalAmount: $totalBet, winAmount:$totalAmount - $totalBet,winTotalAmount:$totalAmount ));
            $bet = $this->calculatorAmount($bet , $rate);
        }

        return $bettingLog;
    }

    public function calculatorAmount(float $bet,float $rate): int
    {
        return intval($bet * $rate);
    }

    public function getMultiplier():int
    {
        return self::$types[$this->type];
    }
}