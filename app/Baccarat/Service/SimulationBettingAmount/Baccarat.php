<?php

namespace App\Baccarat\Service\SimulationBettingAmount;


use App\Baccarat\Service\BettingAmountStrategy\BetStrategyInterface;
use App\Baccarat\Service\Sequence\Sequence;
use Hyperf\Pipeline\Pipeline;
use InvalidArgumentException;

class Baccarat
{
    protected int $issue = 0;

    public function __construct(protected Pipeline $pipeline,protected array $strategies = []){}

    public function addStrategy(BetStrategyInterface $strategy): void
    {
        $this->strategies[] = $strategy;
    }

    public function getStrategies(): array
    {
        return $this->strategies;
    }

    public function getIssue(): int
    {
        return $this->issue;
    }

    public function getNextIssue(): int
    {
        return $this->issue++;
    }
    public function play(string $sequence): array
    {
        $this->pipeline->through($this->strategies);

        $sequenceArray = str_split($sequence);
        $lastIssue = count($sequenceArray);

        array_map(function ($item) use ($lastIssue){

            if (!in_array($item, [Sequence::WIN->value, Sequence::LOSE->value])) {
                throw new InvalidArgumentException("Invalid character in sequence: $item");
            }

            $currentIssue = $this->getNextIssue();
            $this->pipeline->send(new LotteryLog(issue: $currentIssue, sequence: $item, isLastIssue: $currentIssue === $lastIssue))->thenReturn();

        },$sequenceArray);

        return $this->getStrategyBetLogList();
    }

    public function getStrategyBetLogList(): array
    {
        return array_reduce($this->strategies, function (array $betLogList, BetStrategyInterface $strategy) {

            $betLogList[$strategy->getName()] = $strategy->getBetLog()->toArray();
            return $betLogList;

        }, initial: []);
    }
}
