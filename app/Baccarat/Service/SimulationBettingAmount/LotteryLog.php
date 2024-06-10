<?php

namespace App\Baccarat\Service\SimulationBettingAmount;

class LotteryLog
{
    public function __construct(public string|int $issue, public string $sequence = '',public bool $isLastIssue = false)
    {
    }

    public function getIssue(): int|string
    {
        return $this->issue;
    }

    public function getSequence(): string
    {
        return $this->sequence;
    }

    public function isLastIssue(): bool
    {
        return $this->isLastIssue;
    }
}