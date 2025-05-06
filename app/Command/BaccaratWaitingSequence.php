<?php

declare(strict_types=1);

namespace App\Command;

use App\Baccarat\Model\BaccaratSimulatedBetting;
use App\Baccarat\Model\BaccaratSimulatedBettingLog;
use App\Baccarat\Model\BaccaratStrategyBettingLog;
use App\Baccarat\Model\BaccaratTerraceDeck;
use App\Baccarat\Service\BettingAmountStrategy\FlatNote;
use App\Baccarat\Service\BettingAmountStrategy\LayeredStrategy;
use App\Baccarat\Service\BettingAmountStrategy\MartingaleStrategy;
use App\Baccarat\Service\SimulationBettingAmount\Baccarat;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Database\Model\Collection;
use Psr\Container\ContainerInterface;
use App\Baccarat\Model\BaccaratTerrace;
use App\Baccarat\Service\Sequence\Sequence;
use Hyperf\DbConnection\Db;
use Hyperf\Pipeline\Pipeline;
use App\Baccarat\Service\BettingAmountStrategy\BetStrategyInterface;
use Hyperf\Redis\RedisProxy;
use Hyperf\Redis\RedisFactory;

#[Command]
class BaccaratWaitingSequence extends HyperfCommand
{
    protected int $initialAmount = 2000;

    private const CACHE_KEY_PREFIX = 'baccarat:waiting-sequence:deck:';

    protected int $defaultBet = 50;

    protected array $strategies = [];

    protected RedisProxy $redis;

    public function __construct(
        protected ContainerInterface $container,
        protected RedisFactory $factory,
    ) {
        parent::__construct('baccarat:waiting-sequence');

        $this->redis = $this->factory->get('default');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Test different betting strategies');
    }

    protected function makeBaccarat(): Baccarat
    {
        $baccarat =  new Baccarat(new Pipeline($this->container));

        $this->strategies = [
            [
                'title' => '平注策略',
                'strategy_type' => 'FlatNote',
                'initial_amount' => $this->initialAmount,
                'default_bet' => $this->defaultBet,
            ],
            [
                'title' => '分层策略',
                'strategy_type' => 'Layered',
                'initial_amount' => $this->initialAmount,
                'default_bet' => $this->defaultBet,
            ],
            [
                'title' => '倍投策略',
                'strategy_type' => 'Martingale',
                'initial_amount' => $this->initialAmount,
                'default_bet' => $this->defaultBet,
            ],
        ];

        foreach ($this->strategies as $strategyData) {
            $baccarat->addStrategy($this->createStrategy($strategyData));
        }

        return $baccarat;
    }

    public function handle()
    {
        
        BaccaratTerrace::query()
            ->get()
            ->each(function (BaccaratTerrace $terrace) {
                $this->processTerrace($terrace);
            });

        $this->info('Betting strategies test completed!');
    }


    protected function createStrategy(array $strategyData): BetStrategyInterface
    {
        return match ($strategyData['strategy_type']) {
            'FlatNote' => new FlatNote($strategyData['initial_amount'], $strategyData['default_bet']),
            'Layered' => new LayeredStrategy($strategyData['initial_amount'], $strategyData['default_bet']),
            'Martingale' => new MartingaleStrategy($strategyData['initial_amount'], $strategyData['default_bet']),
            default => throw new \InvalidArgumentException(
                "Unknown strategy type: {$strategyData['strategy_type']}"
            )
        };
    }

    protected function processTerrace(BaccaratTerrace $terrace): void
    {
        $terraceDeckDateList = $this->getTerraceDecksGroupedByDate($terrace);

        foreach ($terraceDeckDateList as $deckDate) {

            $exists = BaccaratSimulatedBetting::where('title', "{$terrace->title}/{$deckDate->date}")->exists();
            if (!$exists) {
                $this->processTerraceDate($terrace, $deckDate);
            }
        }
    }

    protected function getTerraceDecksGroupedByDate(BaccaratTerrace $terrace): Collection
    {
        return BaccaratTerraceDeck::query()
            ->select(Db::raw('DATE(created_at) as date'), Db::raw('COUNT(*) as count'))
            ->where('terrace_id', $terrace->id)
            ->whereRaw('DATE(created_at) != ?', [date('Y-m-d')])
            ->groupBy('date')
            ->get();
    }

    protected function processTerraceDate(BaccaratTerrace $terrace, $deckDate): void
    {
        $baccarat = $this->makeBaccarat();
        $sequence = $this->getSequenceForDate($terrace, $deckDate);
        
        if (empty($sequence)) {
            return;
        }

        var_dump($sequence);

        // return;
        
        $results = $baccarat->play($sequence);
        $this->saveResults($terrace, $deckDate, $results);
    }

    protected function getSequenceForDate(BaccaratTerrace $terrace, $deckDate): string
    {
        $sequence = '';
        
        BaccaratTerraceDeck::query()
            ->where('terrace_id', $terrace->id)
            ->whereDate('created_at', $deckDate->date)
            ->with('baccaratSimulatedBettingLog')
            ->get()
            ->each(function (BaccaratTerraceDeck $deck) use (&$sequence) {
                $sequence .= $this->getSequenceFromDeck($deck);
            });

        return $sequence;
    }

    protected function getSequenceFromDeck(BaccaratTerraceDeck $deck): string
    {
        return $deck->baccaratSimulatedBettingLog
            ->filter(fn(BaccaratSimulatedBettingLog $log) => 
                $log->betting_result !== null && 
                in_array($log->betting_result, [Sequence::WIN->value, Sequence::LOSE->value], true)
            )
            ->reduce(fn($carry, BaccaratSimulatedBettingLog $log) => $carry . $log->betting_result, '');
    }

    protected function saveResults(BaccaratTerrace $terrace, $deckDate, array $results): void
    {
        $simulatedBetting = $this->createSimulatedBetting($terrace, $deckDate);
        $this->saveStrategyLogs($simulatedBetting, $results);
    }

    protected function createSimulatedBetting(BaccaratTerrace $terrace, $deckDate): BaccaratSimulatedBetting
    {
        return BaccaratSimulatedBetting::create([
            'strategy_types' => array_map(fn($strategy) => $strategy['strategy_type'], $this->strategies),
            'initial_amount' => $this->initialAmount,
            'default_bet' => $this->defaultBet,
            'title' => "{$terrace->title}/{$deckDate->date}",
        ]);
    }

    protected function saveStrategyLogs(BaccaratSimulatedBetting $simulatedBetting, array $results): void
    {
        foreach ($results as $strategyType => $logs) {
            foreach ($logs as $log) {
                BaccaratStrategyBettingLog::create([
                    'simulated_betting_id' => $simulatedBetting->id,
                    'strategy_type' => $strategyType,
                    'issue' => $log['issue'],
                    'bet_amount' => $log['bet_amount'],
                    'balance' => $log['total_amount'],
                    'result' => $log['sequence'],
                ]);
            }
        }
    }

    protected function isDeckProcessed(int $deckId): bool
    {
        return (bool)$this->redis->get(self::CACHE_KEY_PREFIX . $deckId);
    }

    protected function markDeckAsProcessed(int $deckId): void
    {
        $this->redis->set(self::CACHE_KEY_PREFIX . $deckId, '1');
    }

    public function deleteDeckCache(int $deckId): bool
    {
       return (bool) $this->redis->del(self::CACHE_KEY_PREFIX . $deckId);
    }
}
