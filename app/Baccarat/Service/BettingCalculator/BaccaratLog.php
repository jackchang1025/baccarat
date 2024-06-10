<?php

namespace App\Baccarat\Service\BettingCalculator;

use Hyperf\Contract\Arrayable;

class BaccaratLog implements Arrayable
{

    public function __construct(protected int $issue,protected float $increaseRate,protected int $betAmount,protected int $totalAmount,protected int $winAmount,protected int $winTotalAmount)
    {
    }

    public function toArray(): array
    {
        return [
            'issue' => $this->issue,
            'increaseRate' => $this->increaseRate,
            'betAmount' => $this->betAmount,
            'totalAmount' => $this->totalAmount,
            'winAmount' => $this->winAmount,
            'winTotalAmount' => $this->winTotalAmount,
        ];
    }
}