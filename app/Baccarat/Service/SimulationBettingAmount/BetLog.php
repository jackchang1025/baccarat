<?php

namespace App\Baccarat\Service\SimulationBettingAmount;

use Hyperf\Contract\Arrayable;

class BetLog implements Arrayable
{
    public function __construct(protected string|int $issue, protected string $sequence = '',protected float $betAmount = 0,protected float $totalAmount = 0){}

    public function getIssue(): int|string
    {
        return $this->issue;
    }

    public function getBetAmount(): float
    {
        return $this->betAmount;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(float $totalAmount): void
    {
        $this->totalAmount = $totalAmount;
    }

    public function getSequence(): string
    {
        return $this->sequence;
    }

    public function setSequence(string $sequence): void
    {
        $this->sequence = $sequence;
    }

    public function toArray(): array
    {
        return[
            'issue' => $this->issue,
            'sequence' => $this->sequence,
            'bet_amount' => $this->betAmount,
            'total_amount' => $this->totalAmount
        ];
    }
}
