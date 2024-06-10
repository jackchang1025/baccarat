<?php

namespace App\Baccarat\Service\BettingAmountStrategy;


use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Service\Sequence\Sequence;
use App\Baccarat\Service\SimulationBettingAmount\BetLog;

class MartingaleStrategy extends Strategy
{

    /**
     * 当前输次数
     * @var int
     */
    protected int $loseCountInCurrent = 0;

    public function getName(): string
    {
        return "Martingale";
    }

    public function calculateCurrentBetAmount(BaccaratSimulatedBettingLog $betLog): float|int
    {
        $this->loseCountInCurrent++;

        if ($betLog->isWin() || $this->loseCountInCurrent >= 5){
            $this->loseCountInCurrent = 0;
            $this->currentBet = $this->defaultBetAmount;
            return $this->currentBet;
        }

        $this->currentBet = $this->currentBet * 2;
        return $this->currentBet;
    }
}
