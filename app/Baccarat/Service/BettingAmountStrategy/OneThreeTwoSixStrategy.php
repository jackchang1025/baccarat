<?php

namespace App\Baccarat\Service\BettingAmountStrategy;

use App\Baccarat\Service\Sequence\Sequence;
use App\Baccarat\Service\SimulationBettingAmount\BetLog;

class OneThreeTwoSixStrategy extends Strategy
{
    /**
     * 当前步骤
     * @var int
     */
    private int $currentStep = 0;
    /**
     * 当前步骤的倍数
     * @var array
     */
    private array $stepMultipliers = [1, 3, 2, 6];

    public function getName(): string
    {
        return '1-3-2-6';
    }

    public function calculateCurrentBetAmount(BetLog $betLog): float|int
    {
        // 重置条件：输或完成四步
        if ($betLog->getSequence() === Sequence::LOSE->value || $this->currentStep >= 3) {

            $this->currentStep = 0;
        }else if ($betLog->getSequence() === Sequence::WIN->value) {
            
            $this->currentStep = min($this->currentStep + 1, 3);
        }

        // 计算基础下注单位
        $multiplier = $this->stepMultipliers[$this->currentStep];

        $bet = $this->defaultBetAmount * $multiplier;
        
        // 10的倍数处理
        $bet = ceil($bet / 10) * 10;
        
        // 不能超过剩余本金
        return min($bet, $this->totalBetAmount);
    }
} 