<?php

namespace App\Baccarat\Service\BettingAmountStrategy;



use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Service\Sequence\Sequence;
use App\Baccarat\Service\SimulationBettingAmount\BetLog;

/**
 * 胜进负减
 */
class WinningAdvancesLosingMinusStrategy extends Strategy
{


    public function __construct(float $totalBetAmount, float $defaultBetAmount,protected float $winRate = 0.01,protected float $loseRate = 0.01)
    {
        parent::__construct($totalBetAmount, $defaultBetAmount);

    }

    public function getName(): string
    {
        return "WinningAdvancesLosingMinus";
    }

    public function calculateCurrentBetAmount(BaccaratSimulatedBettingLog $betLog): float|int
    {
        if ($betLog->isWin()){

            return $this->currentBet = round($this->totalBetAmount * $this->winRate);
        }

        return $this->currentBet = round($this->totalBetAmount * $this->loseRate);
    }
}
