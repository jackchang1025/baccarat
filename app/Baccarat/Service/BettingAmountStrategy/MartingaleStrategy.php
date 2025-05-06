<?php

namespace App\Baccarat\Service\BettingAmountStrategy;



use App\Baccarat\Service\Sequence\Sequence;
use App\Baccarat\Service\SimulationBettingAmount\BetLog;

class MartingaleStrategy extends Strategy
{

    public function getName(): string
    {
        return "Martingale";
    }

    public function calculateCurrentBetAmount(BetLog $betLog): float|int
    {
        if ($betLog->getSequence() == Sequence::LOSE->value) {

            //当前投注金额 乘以 2
            return $this->currentBet = $this->currentBet * 2;
        }

        return $this->currentBet = $this->defaultBetAmount;
    }


}
