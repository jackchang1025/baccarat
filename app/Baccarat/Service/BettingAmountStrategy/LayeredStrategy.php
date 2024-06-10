<?php

namespace App\Baccarat\Service\BettingAmountStrategy;


use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Service\Sequence\Sequence;
use App\Baccarat\Service\SimulationBettingAmount\BetLog;

class LayeredStrategy extends Strategy
{
    protected array $layerBets = [];

    /**
     * 当前层数
     * @var int
     */
    protected int $currentLayer = 1;
    /**
     * 当前层的投注次数
     * @var int
     */
    protected int $betCountInCurrentLayer = 0;
    /**
     * 当前层的输赢次数
     * @var int
     */
    protected int $winCountInCurrentLayer = 0;

    public function __construct(float $totalBetAmount, float $defaultBetAmount)
    {
        parent::__construct($totalBetAmount, $defaultBetAmount);

        $this->initializeLayerBets();
    }

    public function reset(): void
    {
        parent::reset();

        $this->currentLayer = 1;
        $this->betCountInCurrentLayer = 0;
        $this->winCountInCurrentLayer = 0;
    }

    protected function initializeLayerBets(): void
    {
        // 根据默认投注金额生成每层的投注金额
        for ($i = 1; $i <= 9; $i++) {
            $this->layerBets[$i] = $this->defaultBetAmount * $i;
        }
    }

    public function getName(): string
    {
        return "Layered";
    }

    public function calculateCurrentBetAmount(BaccaratSimulatedBettingLog $betLog): float|int
    {

        $this->betCountInCurrentLayer++;

        if ($betLog->isWin()) {
            $this->winCountInCurrentLayer++;

            if ($this->betCountInCurrentLayer === 1) {
                // 如果第一注就中,马上退一层,并重置计数器
                $this->currentLayer = max(1, $this->currentLayer - 1);
                $this->resetLayerCounters();
                $this->currentBet = $this->layerBets[$this->currentLayer];
                return $this->currentBet;
            }
        }

        if ($this->betCountInCurrentLayer === 3) {
            // 三注完成后，根据输赢情况调整层数

            switch ($this->winCountInCurrentLayer){
                case 0:
                    //赢0次 输三次 每层共输三注就进二层，输三注
                    $this->currentLayer = min(9, $this->currentLayer + 2);
                    break;
                case 1:
                    //赢1次 输2次 每层共输二注就进一层，输一注
                    $this->currentLayer = min(9, $this->currentLayer + 1);
                    break;
                case 2:
                    //赢2次 输1次 每层共赢一注就退一层，赢一注
                    $this->currentLayer = max(1, $this->currentLayer - 1);
                    break;
                default:
                    break;
            }
            // 重置计数器
            $this->resetLayerCounters();
        }

        $this->currentBet = $this->layerBets[$this->currentLayer];
        return $this->currentBet;
    }

    protected function resetLayerCounters(): void
    {
        $this->betCountInCurrentLayer = 0;
        $this->winCountInCurrentLayer = 0;
    }
}
