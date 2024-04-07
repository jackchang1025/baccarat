<?php

namespace App\Baccarat\Service\BaccaratDealer;


use App\Baccarat\Service\LotteryResult;
use Hyperf\Collection\Collection;
use Hyperf\Coroutine\Coroutine;

class BaccaratDealerService
{
    protected array $deck = []; // 牌堆
    protected Collection $roundResults;

    /**
     * 当前期号
     * @var int|string
     */
    protected int|string $currentIssue = 0;

    protected array $odds = [
        'player' => 2,  // 假设玩家赢的赔率为 1:1
        'banker' => 1.95,  // 假设庄家赢的赔率为 0.95:1，通常庄家赢会抽取5%的佣金
        'tie' => 8,  // 假设平局的赔率为 8:1
    ];

    protected string $transformationResults = '';


    /**
     *  构造函数，用于初始化牌堆。
     * @param int $decksCount 牌堆中的牌副数，默认为8副。
     * @param float|int $sleep 睡眠时间
     * @param int $cutCardPosition 切牌点 当达到切牌点（是否结束这一局）
     */
    public function __construct(
        protected int $decksCount = 8,
        protected float|int $sleep = 1,
        protected int $cutCardPosition = 10,
        protected ShuffleDeckInterface $shuffleDeck = new ShuffleDeck(5)
    )
    {
        //初始化牌堆
        $this->initializeDeck();
        //洗牌并切牌
        $this->shuffleAndCutDeck();

        $this->roundResults = new Collection();
    }

    public function getOdds(string $key): float|int
    {
        return $this->odds[$key] ?? 0;
    }

    public function getCurrentIssue(): int
    {
        return $this->currentIssue;
    }

    public function getTransformationResults(): string
    {
        return $this->transformationResults;
    }

    public function setSleep(float|int $sleep): void
    {
        $this->sleep = $sleep;
    }

    /**
     * 初始化牌堆。
     */
    protected function initializeDeck(): void
    {
        $this->deck = array_reduce(
            array_fill(0, $this->decksCount, $this->aDeckOfCards()),
            fn($carry, $item) => array_merge($carry, $item),
            []
        );
    }

    /**
     * 生成一副牌
     * @return array
     */
    protected function aDeckOfCards(): array
    {
        //$suits = array_fill(0,4,['♠', '♥', '♣', '♦']);
        return array_reduce(
            array_fill(0, 4, ['A', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K']),
            fn($carry, $item) => array_merge($carry, $item),
            []
        );
    }

    public function getRoundResults(): Collection
    {
        return $this->roundResults;
    }

    /**
     * 洗牌函数，用于随机打乱牌堆中的牌。
     */
    protected function shuffleDeck(): static
    {
        $this->deck = $this->shuffleDeck->shuffleDeck($this->deck);
        return $this;
    }

    public function shuffleAndCutDeck(): static
    {
        //洗牌
        $this->shuffleDeck();
        //模拟切牌
        $cutPosition = random_int(1, count($this->deck) - 1);
        $cutDeck = array_slice($this->deck, $cutPosition);
        $remainingDeck = array_slice($this->deck, 0, $cutPosition);
        $this->deck = array_merge($cutDeck, $remainingDeck);

        return $this;
    }

    /**
     * 设置切牌点。
     * @param int $position 设置切牌点的位置，即剩余多少张牌时重新洗牌。
     */
    public function setCutCardPosition(int $position): void
    {
        $this->cutCardPosition = $position;
    }

    /**
     * 运行发牌程序
     */
    public function run(): void
    {
        while (!$this->isEnd()) {

            Coroutine::sleep($this->sleep);

            $this->roundResults->push(value: $this->dealHands());
        }
    }

    /**
     * 是否达到切牌点（是否结束这一局）
     * @return bool
     */
    public function isEnd(): bool
    {
        return count($this->deck) <= $this->cutCardPosition;
    }

    /**
     * 处理一轮发牌。
     * @return LotteryResult 返回包含玩家和庄家手牌的数组。
     */
    public function dealHands(): LotteryResult
    {
        $playerHand = array($this->dealCard(), $this->dealCard());
        $bankerHand = array($this->dealCard(), $this->dealCard());

        // 根据规则决定是否发第三张牌
        if ($this->shouldDealThirdCard($playerHand, $bankerHand)) {
            $playerHand[] = $this->dealCard();
        }

        if ($this->shouldDealThirdCard($bankerHand, $playerHand, true)) {
            $bankerHand[] = $this->dealCard();
        }

        $this->currentIssue = time();
        // 计算开奖结果，这里假设有一个方法 calculateResult 来计算结果
        $result = $this->calculateResult($playerHand, $bankerHand);


        $this->transformationResults .= $result;


        return new LotteryResult(
            terrace: $this->currentIssue,
            issue: $this->currentIssue,
            status: 'waiting',
            transformationResult: $result,
            playerHand: $playerHand,
            bankerHand: $bankerHand
        );
    }

    protected function calculateResult(array $playerHand, array $bankerHand): string {
        $playerPoints = $this->calculatePoints($playerHand);
        $bankerPoints = $this->calculatePoints($bankerHand);

        if ($playerPoints > $bankerPoints) {
            return LotteryResult::PLAYER;
        } elseif ($playerPoints < $bankerPoints) {
            return LotteryResult::BANKER;
        } else {
            return LotteryResult::TIE;
        }
    }

    /**
     * 发牌函数，用于给玩家和庄家各发两张牌。
     * @return string 返回一个包含玩家和庄家手牌的数组。
     */
    private function dealCard(): string
    {
        return array_shift($this->deck);
    }

    /**
     * 根据规则决定是否发第三张牌
     * @param array $hand
     * @param array $otherHand
     * @param bool $isBanker
     * @return bool
     */
    protected function shouldDealThirdCard(array $hand, array $otherHand, bool $isBanker = false): bool
    {
        $handPoints      = $this->calculatePoints($hand);
        $otherHandPoints = $this->calculatePoints($otherHand);

        if ($handPoints >= 8 || $otherHandPoints >= 8) {
            // 如果任一方为自然赢家，则不再发牌
            return false;
        }

        if ($isBanker) {
            // 庄家的发牌规则
            if (count($hand) == 2) {
                // 如果庄家只有两张牌
                if ($handPoints <= 2) {
                    return true;
                }
                if ($handPoints == 3 && (!isset($otherHand[2]) || $otherHand[2] != '8')) {
                    return true;
                }
                if ($handPoints == 4 && (!isset($otherHand[2]) || in_array(
                            $otherHand[2],
                            array('2', '3', '4', '5', '6', '7')
                        ))) {
                    return true;
                }
                if ($handPoints == 5 && (!isset($otherHand[2]) || in_array($otherHand[2], array('4', '5', '6', '7')))) {
                    return true;
                }
                if ($handPoints == 6 && (!isset($otherHand[2]) || in_array($otherHand[2], array('6', '7')))) {
                    return true;
                }
            }

            return false;
        } else {
            // 玩家的发牌规则
            return $handPoints <= 5;
        }
    }

    /**
     * 计算手牌点数
     * @param array $hand
     * @return int
     */
    function calculatePoints(array $hand): int
    {
        $points = array_reduce(
            $hand,
            fn($carry, $item) => is_numeric($item)
                ? $carry + $item
                : $carry + ['A' => 1, 'J' => 0, 'Q' => 0, 'K' => 0,][$item],
            0
        );

        return $points % 10;
    }
}
