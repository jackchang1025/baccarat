<?php

namespace App\Baccarat\Service\BettingAmountStrategy;

use App\Baccarat\Service\SimulationBettingAmount\LotteryLog;
use Closure;
use Hyperf\Collection\Collection;

interface BetStrategyInterface
{
    public function handle(Collection $collection): mixed;

    public function getName(): string;

    public function getTotalBetAmount(): float;

    public function getBetLog(): Collection;
}
