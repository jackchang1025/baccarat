<?php

namespace App\Baccarat\Service\BettingAmountStrategy;


use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Service\SimulationBettingAmount\BetLog;

class FlatNote extends Strategy
{

    public function getName(): string
    {
        return 'FlatNote';
    }

    public function calculateCurrentBetAmount(BaccaratSimulatedBettingLog $betLog): float|int
    {
        return $this->currentBet;
    }
}
