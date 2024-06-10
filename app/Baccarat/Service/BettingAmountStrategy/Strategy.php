<?php

namespace App\Baccarat\Service\BettingAmountStrategy;


use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Service\BaccaratDealer\BaccaratDealerService;
use App\Baccarat\Service\Sequence\Sequence;
use App\Baccarat\Service\SimulationBettingAmount\BetLog;
use Hyperf\Collection\Collection;
use PhpParser\Node\Stmt\If_;

abstract class Strategy implements BetStrategyInterface
{
    static array $strategy = [
        'MartingaleStrategy' => '倍投',
        'LayeredStrategy'    => '平注分层补偿缆',
        'FlatNote'           => '平注',
    ];

    protected float $historyTotalBetAmount = 0;

    protected float $currentBet = 0;

    protected ?Collection $betLog = null;

    /**
     * @param float $totalBetAmount 总投注金额
     * @param float $defaultBetAmount 默认投注金额
     */
    public function __construct(protected float $totalBetAmount, protected float $defaultBetAmount)
    {
        if ($totalBetAmount < $defaultBetAmount || $defaultBetAmount < 20) {
            throw new \InvalidArgumentException('totalBetAmount or defaultBetAmount is invalid');
        }
        $this->currentBet = $defaultBetAmount;
        $this->historyTotalBetAmount = $totalBetAmount;
    }

    public function getTotalBetAmount(): float
    {
        return $this->totalBetAmount;
    }

    public function getBetLog(): Collection
    {
        return $this->betLog;
    }

    final public function handle(Collection $collection): Collection
    {
        $this->betLog = clone $collection;

        $carry = new BetLog(issue: $collection->first()->issue, betAmount: $this->currentBet);

        $this->betLog->reduce(function (BetLog $carry, BaccaratSimulatedBettingLog $bettingLog) {

            if ($carry->getBetAmount()) {

                //更新投注金额
                if ($bettingLog->isWin()) {

                    //根据赔率计算输赢金额
                    $this->totalBetAmount += round($carry->getBetAmount() * BaccaratDealerService::getOdds($bettingLog->betting_value));
                } else {
                    $this->totalBetAmount -= round($carry->getBetAmount());
                }

                $bettingLog->setAttribute('total_amount', $this->totalBetAmount);
                $bettingLog->setAttribute('bet_amount', $carry->getBetAmount());

                $carry->setBetAmount(0);
            }

            //判断是否最后一期
            if ($this->betLog->last()->issue !== $bettingLog->issue) {

                //如果总金额小于 20 ,重置投注金额
                if ($this->checkTotalBetAmount()) {
                    $this->reset();
                }

                //根据当前投注日志计算下一期的投注金额
                $this->currentBet = $this->calculateCurrentBetAmount($bettingLog);

                $carry->setBetAmount($this->checkCurrentBetAmount());
            }

            return $carry;
        }, $carry);

        return $this->betLog;
    }

    final protected function checkCurrentBetAmount(): float|int
    {
        //投注金额不能小于 20
        if ($this->currentBet < 20) {
            return $this->currentBet = 20;
        }
        //投注金额不能大于总金额
        if ($this->currentBet > $this->totalBetAmount) {
            return $this->currentBet = $this->totalBetAmount;
        }
        return $this->currentBet;
    }

    final protected function checkTotalBetAmount(): bool
    {
        return $this->totalBetAmount < 20;
    }

    abstract public function calculateCurrentBetAmount(BaccaratSimulatedBettingLog $betLog): float|int;

    public function reset(): void
    {
        $this->currentBet = $this->defaultBetAmount;
        $this->totalBetAmount = $this->historyTotalBetAmount;
    }
}
