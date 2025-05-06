<?php

namespace App\Baccarat\Service\BettingAmountStrategy;

use App\Baccarat\Service\Sequence\Sequence;
use App\Baccarat\Service\SimulationBettingAmount\BetLog;
use App\Baccarat\Service\SimulationBettingAmount\LotteryLog;
use Hyperf\Collection\Collection;

abstract class Strategy implements BetStrategyInterface
{
    public static array $strategy = [
        'MartingaleStrategy'=>'倍投',
        'LayeredStrategy'=>'平注分层补偿缆',
        'FlatNote'=>'平注',
        'FixedRatioStrategy' => '固定比例',
        'OneThreeTwoSixStrategy' => '1-3-2-6',
    ];

    /**
     * @param float $totalBetAmount 总投注金额
     * @param float $defaultBetAmount 默认投注金额
     * @param Collection $betLog 投注记录
     * @param float $currentBet 当前投注金额
     */
    public function __construct(protected float $totalBetAmount, protected float $defaultBetAmount,protected Collection $betLog = new Collection(),protected float $currentBet = 0)
    {
        $this->currentBet = $defaultBetAmount;
        $this->init();
    }

    public function getTotalBetAmount(): float
    {
        return $this->totalBetAmount;
    }

    public function getBetLog(): Collection
    {
        return $this->betLog;
    }

    public function init()
    {
        $this->pushBet(0,$this->currentBet);
    }

    public function pushBet(int|string $issue, float $betAmount): void
    {
        $this->totalBetAmount -= $betAmount; // 本轮的投注金额
        $this->betLog->push(new BetLog(issue: $issue,betAmount: $betAmount,totalAmount: $this->totalBetAmount));
    }

    public function updateSequence(BetLog $betLog,string $sequence): void
    {
        $betLog->setSequence($sequence);
    }

    public function updateTotalBetAmount(BetLog $betLog): void
    {
        if ($betLog->getSequence() == Sequence::WIN->value) {
            $this->totalBetAmount += $betLog->getBetAmount() * 2;
            $betLog->setTotalAmount($this->totalBetAmount);
        }
    }

    public function handle(LotteryLog $betLog,\Closure $next):mixed
    {
        /**
         * @var BetLog $lastBetLog
         */
        $lastBetLog = $this->betLog->last();

        $this->updateSequence($lastBetLog,$betLog->sequence);
        $this->updateTotalBetAmount($lastBetLog);

        if (!$betLog->isLastIssue()) {

            //当前投注金额不能超过总投注金额
            $currentBet = min($this->calculateCurrentBetAmount($lastBetLog),$this->totalBetAmount);

            $this->pushBet($betLog->issue + 1, $currentBet);
        }

        return $next($betLog);
    }

    abstract public function calculateCurrentBetAmount(BetLog $betLog):float|int;
}
