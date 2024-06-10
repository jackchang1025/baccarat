<?php

namespace App\Baccarat\Service\BettingAmountStrategy;

use App\Baccarat\Model\BaccaratSimulatedBettingLog;

class WinEnter extends Strategy
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

    public function __construct(float $totalBetAmount, float $defaultBetAmount, protected int $maxLayer = 10)
    {
        parent::__construct($totalBetAmount, $defaultBetAmount);

        $this->initializeLayerBets();
    }

    public function initializeLayerBets(): void
    {
        $defaultBetAmount = $this->defaultBetAmount;

        // 根据默认投注金额生成每层的投注金额
        for ($layerIndex = 1; $layerIndex <= $this->maxLayer; $layerIndex++) {

            $defaultBetAmount = $this->generateLayerBets($layerIndex, $defaultBetAmount);
        }
    }


    private function generateLayerBets(int $layerIndex,float $betAmount): float
    {
        // 以更清晰的方式生成每层的投注金额
        $this->layerBets[$layerIndex] = [
            $betAmount,
            $betAmount,
            $betAmount * 2,
        ];

        return $betAmount * 2;
    }

    public function getName(): string
    {
        return "WinEnter";
    }

    public function calculateCurrentBetAmount(BaccaratSimulatedBettingLog $betLog): float|int
    {

        if ($betLog->isWin()) {

            $this->betCountInCurrentLayer++;
            if ($this->betCountInCurrentLayer >= 3) {
                $this->betCountInCurrentLayer = 0;
                $this->advanceLayer();
            }

        } else {
            $this->resetCurrentLayer();
        }

        return $this->getCurrentBetAmount();
    }

    public function resetCurrentLayer(): void
    {
        $this->betCountInCurrentLayer = 0;
        $this->currentLayer = 1;
    }

    public function reset(): void
    {
        parent::reset();
       $this->resetCurrentLayer();
    }

    private function advanceLayer(): void
    {
        $this->currentLayer++;
        if ($this->currentLayer > $this->maxLayer) {
            $this->currentLayer = 1; // 可以考虑是否需要处理超过最大层数的情况
        }
    }

    private function getCurrentBetAmount(): float|int
    {
        return $this->layerBets[$this->currentLayer][$this->betCountInCurrentLayer] ?? $this->defaultBetAmount;
    }
}