<?php

namespace App\Baccarat\Service\SimulationBettingAmount;


use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Service\BettingAmountStrategy\BetStrategyInterface;
use App\Baccarat\Service\LotteryResult;
use App\Baccarat\Service\Sequence\Sequence;
use Hyperf\Database\Model\Collection;
use Hyperf\Pipeline\Pipeline;
use InvalidArgumentException;

class Baccarat
{
    public function __construct(protected array $strategies = [])
    {
    }

    public function addStrategy(BetStrategyInterface $strategy): void
    {
        $this->strategies[] = $strategy;
    }

    public function getStrategies(): array
    {
        return $this->strategies;
    }

    public function transformation(string $sequence): Collection
    {
        $collection = new Collection();
        $sequence = str_split($sequence);
        if (!empty($sequence)){
            foreach ($sequence as $key => $item){
                if (!in_array($item, [Sequence::WIN->value, Sequence::LOSE->value])) {
                    throw new InvalidArgumentException("Invalid character in sequence: $item");
                }

                $collection->push(BaccaratSimulatedBettingLog::make(['betting_result' => $item, 'issue' => $key,'betting_value'=>LotteryResult::PLAYER]));
            }
        }

        return $collection;
    }

    public function play(string|Collection $sequence): array
    {
        $sequence = is_string($sequence) ? $this->transformation($sequence) : $sequence;

        return $this->handle($sequence);
    }

    protected function handle(Collection $collection):array
    {
        return array_reduce($this->strategies, function (array $betLogList, BetStrategyInterface $strategy) use ($collection) {

            $data = $strategy->handle($collection)->toArray();

            if (empty($betLogList['wanting_sequence'])) {
                $betLogList['wanting_sequence'] = $data;
            }

            $betLogList[$strategy->getName()] = $data;

            return $betLogList;

        }, initial: []);
    }
}
