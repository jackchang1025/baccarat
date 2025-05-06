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
use Psr\Container\ContainerInterface;
use App\Baccarat\Model\BaccaratTerrace;
use App\Baccarat\Service\Sequence\Sequence;
use Hyperf\Pipeline\Pipeline;
use App\Baccarat\Service\BettingAmountStrategy\BetStrategyInterface;
use App\Baccarat\Service\BettingAmountStrategy\FixedRatioStrategy;
use App\Baccarat\Service\BettingAmountStrategy\OneThreeTwoSixStrategy;
#[Command]
class BaccaratRandomSequence extends HyperfCommand
{
    protected int $initialAmount = 2000;
    protected int $defaultBet = 50;
    protected array $strategies = [];
    protected int $minSequenceLength = 100;
    protected array $processedDeckIds = [];

    

    public function __construct(
        protected ContainerInterface $container,
        
    ) {
        parent::__construct('baccarat:random-sequence');

        
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Test betting strategies with random terrace and deck')
            ->addOption(
                'sequence',
                's',
                \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED,
                'Custom sequence to use (e.g. 101010)',
                null
            );
    }

    public function handle()
    {
        $sequence = $this->input->getOption('sequence');
        
        if (empty($sequence)) {
            $this->error('Could not get valid sequence');
            return;
        }

        // 验证序列格式
        if (!preg_match('/^[01]+$/', $sequence)) {
            $this->error('Invalid sequence format. Only 0 and 1 are allowed.');
            return;
        }

        $baccarat = $this->makeBaccarat();
        $results = $baccarat->play($sequence);
        
        // 保存结果时使用当前时间作为日期
        $this->saveResults(null, (object)['date' => date('Y-m-d')], $results);
        
        $this->info('Random sequence betting strategies test completed!');
    }

    protected function getValidSequence(): string
    {
        $sequence = '';
        $this->processedDeckIds = [];  // 重置已处理牌靴列表

        while (strlen($sequence) < $this->minSequenceLength) 
        {
            try {
                $terrace = $this->getRandomTerrace();
                $deck = $this->getRandomDeck($terrace);

                $sequence .= $this->getSequenceFromDeck($deck);

                var_dump($sequence);
            } catch (\RuntimeException $e) {
                // 如果某个台的所有牌靴都处理完了,继续尝试其他台
                continue;
            }
        }

        return $sequence;
    }

    protected function getRandomTerrace(): BaccaratTerrace
    {
        $ids = BaccaratTerrace::query()
            ->select('id')
            ->has('baccaratTerraceDeck')
            ->pluck('id')
            ->toArray();
        
        if (empty($ids)) {
            throw new \RuntimeException('No terrace with decks found');
        }
        
        $randomId = $ids[array_rand($ids)];

        var_dump($randomId);
        
        return BaccaratTerrace::query()
            ->where('id', $randomId)
            ->firstOrFail();
    }

    protected function getRandomDeck(BaccaratTerrace $terrace): BaccaratTerraceDeck
    {
        $ids = BaccaratTerraceDeck::query()
            ->select('id')
            ->where('terrace_id', $terrace->id)
            ->whereNotIn('id', $this->processedDeckIds)  // 排除已处理的牌靴
            ->pluck('id')
            ->toArray();
        
        if (empty($ids)) {
            throw new \RuntimeException('No more unprocessed decks found for terrace: ' . $terrace->id);
        }
        
        $randomId = $ids[array_rand($ids)];
        $this->processedDeckIds[] = $randomId;  // 将新处理的牌靴ID加入列表
        
        return BaccaratTerraceDeck::query()
            ->where('id', $randomId)
            ->with('baccaratSimulatedBettingLog')
            ->firstOrFail();
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

    protected function makeBaccarat(): Baccarat
    {
        $baccarat = new Baccarat(new Pipeline($this->container));

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
            [
                'title' => '固定比例策略',
                'strategy_type' => 'FixedRatio',
                'initial_amount' => $this->initialAmount,
                'default_bet' => $this->defaultBet,
            ],
            [
                'title' => '1-3-2-6策略',
                'strategy_type' => 'oneThreeTwoSix',
                'initial_amount' => $this->initialAmount,
                'default_bet' => $this->defaultBet,
            ],
        ];

        foreach ($this->strategies as $strategyData) {
            $baccarat->addStrategy($this->createStrategy($strategyData));
        }

        return $baccarat;
    }

    protected function createStrategy(array $strategyData): BetStrategyInterface
    {
        return match ($strategyData['strategy_type']) {
            'FlatNote' => new FlatNote($strategyData['initial_amount'], $strategyData['default_bet']),
            'Layered' => new LayeredStrategy($strategyData['initial_amount'], $strategyData['default_bet']),
            'Martingale' => new MartingaleStrategy($strategyData['initial_amount'], $strategyData['default_bet']),
            'FixedRatio' => new FixedRatioStrategy($strategyData['initial_amount'], $strategyData['default_bet']),
            'oneThreeTwoSix' => new OneThreeTwoSixStrategy($strategyData['initial_amount'], $strategyData['default_bet']),
            default => throw new \InvalidArgumentException(
                "Unknown strategy type: {$strategyData['strategy_type']}"
            )
        };
    }

    protected function saveResults(?BaccaratTerrace $terrace, $deckDate, array $results): void
    {
        $simulatedBetting = $this->createSimulatedBetting($terrace, $deckDate);
        $this->saveStrategyLogs($simulatedBetting, $results);
    }

    protected function createSimulatedBetting(?BaccaratTerrace $terrace, $deckDate): BaccaratSimulatedBetting
    {
        $title = $terrace ? "{$terrace->title}/{$deckDate->date}" : "Random/{$deckDate->date}";
        
        return BaccaratSimulatedBetting::create([
            'strategy_types' => array_map(fn($strategy) => $strategy['strategy_type'], $this->strategies),
            'initial_amount' => $this->initialAmount,
            'default_bet' => $this->defaultBet,
            'title' => $title,
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

 
} 