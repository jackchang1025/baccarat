<?php

namespace App\Baccarat\Service\BettingAmountStrategy;

use App\Baccarat\Service\SimulationBettingAmount\BetLog;

class FixedRatioStrategy extends Strategy
{
    public function getName(): string
    {
        return 'FixedRatio';
    }

    public function calculateCurrentBetAmount(BetLog $betLog): float|int
    {
        // 取当前本金 10% 作为下注金额，最低下注 20单位
        $bet = max($this->totalBetAmount * 0.1, 20);

        // 投注金额只能是 10 的倍数，例如：10、20、30、40、50、60、70、80、90、100
        $bet = ceil($bet / 10) * 10;

        // 不能超过剩余本金
        return min($bet, $this->totalBetAmount);
    }
} 