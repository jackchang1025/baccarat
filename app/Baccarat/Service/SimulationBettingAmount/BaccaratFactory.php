<?php

namespace App\Baccarat\Service\SimulationBettingAmount;

use App\Baccarat\Service\BettingAmountStrategy\BetStrategyInterface;
use Hyperf\Contract\ConfigInterface;

class BaccaratFactory
{

    public function __construct(protected ConfigInterface $config)
    {
    }

    public function create(float $totalBetAmount, float $defaultBetAmount): Baccarat
    {
        $strategyList = $this->config->get('baccarat.strategy');

        $baccarat = make(Baccarat::class);

        if (!empty($strategyList)) {
            foreach ($strategyList as $strategy) {

                $strategyInstance = $this->createStrategyInstance($strategy, $totalBetAmount, $defaultBetAmount);

                $baccarat->addStrategy($strategyInstance);
            }
        }
        return $baccarat;
    }

    private function createStrategyInstance(string|callable $strategy, float $totalBetAmount, float $defaultBetAmount): BetStrategyInterface
    {
        if (is_callable($strategy)) {
            return $strategy($totalBetAmount, $defaultBetAmount);
        } elseif (is_string($strategy)) {
            return make($strategy, ['totalBetAmount' => $totalBetAmount, 'defaultBetAmount' => $defaultBetAmount]);
        } else {
            throw new \InvalidArgumentException("Strategy type not supported: must be callable or a string.");
        }
    }
}