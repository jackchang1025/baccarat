<?php

namespace App\Baccarat\Service;

use App\Baccarat\Model\BaccaratLotteryLog;
use App\Baccarat\Model\BaccaratTerraceDeck;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Model;

class LotteryResult
{
    const  PLAYER = 'P';//玩家
    const  BANKER = 'B';

    const  TIE = 'T';

    const BETTING_LOSE = '0';
    const BETTING_WIN = '1';

    const BETTING_TIE = '2';

    private const PLAYER_INDEXES = [0, 2, 4];
    private const BANKER_INDEXES = [1, 3, 5];

    protected static array $table = [
        //百家乐  300180 百家乐mx4
        "30471", "30474", "30011", "30012", "30013", "30016", "30017", "300136", "300137", "300182",
        "300183", "300184", "300185", "300186", "300187", "300157", "300158", "300159", "300160",
        "300161", "300177", "300178", "300179", "300180", "300181", "300188", "300189", "300190",
        //区块链百家
        "30431", "30432", "30433", "30434", "30435", "30436", "30251",
        "30252", "30253", "30254", "30255", "30256", "30257", "30271", "30272", "30273",
        //金臂百家
        "3001107", "3001108", "3001109", "3001110", "3001111", "3001112", "3001113", "3001114", "3001115", "3001116",
        //独家
//        "30121", "30111", "30321", "30322", "30323", "30151", "30371", "30372", "30373", "30374", "30375", "30376",
//        "30039", "30261", "30262", "30263", "30481", "30482", "30483", "30291", "30292", "30293", "30411", "30412",
//        "30413", "30311", "30312", "30313", "30301", "30302", "30303", "30381", "30382", "30383", "30361", "30362",
//        "30363", "30461", "30462", "30463"
    ];


    public function __construct(
        public string     $terrace,
        public ?string    $issue = null,
        public ?string    $result = null,
        public ?string    $status = null,
        public ?string    $rn = null,
        public array      $data = [],
        protected ?string $transformationResult = null,
        protected ?array  $playerHand = null,
        protected ?array  $bankerHand = null
    )
    {

    }

    public function setTransformationResult(string $transformationResult): void
    {
        $this->transformationResult = $transformationResult;
    }

    public static function fromArray(string $terrace, array $data): LotteryResult
    {
        return new self(
            terrace: $terrace,
            issue: $data['rs'] ?? null,
            result: $data['pk'] ?? null,
            status: $data['st'] ?? null,
            rn: $data['rn'] ?? null,
            data: $data
        );
    }

    public function isBetting(): bool
    {
        return $this->status === 'betting';
    }

    public function isWaiting(): bool
    {
        return $this->status === 'waiting';
    }

    /**
     * 获取牌靴号
     * @return string|null
     */
    public function getDeckNumber(): ?string
    {
        if (empty($this->rn)) {
            return null;
        }
        // 使用正则表达式将非数字字符和-替换为空字符串
        $str = preg_replace('/[^0-9-]/', '', $this->rn);

        return transform($str, fn($str) => explode('-', $str)[0] ?: null);
    }

    /**
     * 获取下一张牌靴的牌靴号
     * @return string|null
     */
    public function getLastDeckNumber(): ?string
    {
        //首先获取当前牌靴号，然后转换为数字类型 并且加1，最后转换为字符串类型
        return transform($this->getDeckNumber(), fn($deckNumber) => (string)(((int)$deckNumber) - 1));
    }

    /**
     * 获取当前牌靴的牌号
     * @return string|null
     */
    public function getNumber(): ?string
    {
        if (empty($this->rn)) {
            return null;
        }
        // 使用正则表达式将非数字字符和-替换为空字符串
        $str = preg_replace('/[^0-9-]/', '', $this->rn);

        return transform($str, fn($str) => explode('-', $str)[1] ?: null);
    }

    public function toArray(): array
    {
        return [
            'terrace' => $this->terrace,
            'issue' => $this->issue,
            'result' => $this->result,
            'status' => $this->status,
            'rn' => $this->rn,
        ];
    }

    public function getTransformationResult(): ?string
    {
        $playerHand = $this->getPlayerHand();
        $bankerHand = $this->getBankerHand();

        return $this->transformationResult === null && $playerHand && $bankerHand
            ? $this->calculateResult(playerHand: $playerHand, bankerHand: $bankerHand)
            : $this->transformationResult;
    }

    public function checkLotteryResults(string $bettingValue): string
    {
        if ($this->getTransformationResult() === self::TIE){
            return self::BETTING_TIE;
        }
        return $bettingValue === $this->getTransformationResult() ? self::BETTING_WIN : self::BETTING_LOSE;
    }

    private function parseHand(array $resultArray, array $indexes): array
    {
        return array_values(array_filter($resultArray, fn($key) => in_array($key, $indexes), ARRAY_FILTER_USE_KEY));
    }

    public function getPlayerHand(): ?array
    {
        return $this->playerHand ??= $this->parseHand($this->parseResult() ?? [], self::PLAYER_INDEXES);
    }

    public function getBankerHand(): ?array
    {
        return $this->bankerHand ??= $this->parseHand($this->parseResult() ?? [], self::BANKER_INDEXES);
    }

    public function isFirstLottery(): bool
    {
        if (isset($this->data['map'])) {
            try {
                return count(array_filter(explode(',', $this->data['map']))) == 1;
            } catch (\Exception|\Throwable) {
                return false;
            }
        }
        return false;
    }

    /**
     * 是否第一局
     * @return bool
     */
    public function isLotteryOne(): bool
    {
        return $this->getNumber() == '1';
    }

    public function parseResult(): ?array
    {
        //S.10,D.1,S.9,C.6,, 如何去除字符中字母和. 最终转换结果为数组 [10,1,9,6,2,9]
        if (!$this->result) {
            return null;
        }

        //将 S.10,D.1,S.9,C.6,,切割为数组
        // 使用正则表达式将非数字字符替换为空字符串
        $str = preg_replace('/[^0-9,]/', '', $this->result);

        // 根据逗号分割字符串为数组
        $resultArray = explode(',', $str);

        // 移除空元素并将字符串转换为整数
        $resultArray = array_map('intval', array_filter($resultArray));

        if (count($resultArray) <= 3) {
            return null;
        }

        return $resultArray;
    }

    /**
     * 计算结果
     * @param array $playerHand
     * @param array $bankerHand
     * @return string
     */
    protected function calculateResult(array $playerHand, array $bankerHand): string
    {
        $playerPoints = $this->calculatePoints($playerHand);
        $bankerPoints = $this->calculatePoints($bankerHand);

        return match (true) {
            $playerPoints > $bankerPoints => self::PLAYER,
            $playerPoints < $bankerPoints => self::BANKER,
            $playerPoints == $bankerPoints => self::TIE,
        };
    }

    function getUniqueX($coordinates, $x, $y)
    {
        foreach ($coordinates as $coordinate) {
            if ($coordinate['x'] === $x && $coordinate['y'] === $y) {
                return $this->getUniqueX($coordinates, $x + 1, $y);
            }
        }
        return $x;
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

    public function isBaccarat(): bool
    {
        return in_array(str_replace('-', '', $this->terrace), self::$table);
    }

    public function __toString(): string
    {
        return "terrace:{$this->terrace} issue:{$this->issue} result:{$this->result} transformationResult:{$this->getTransformationResult()} status:{$this->status} rn:{$this->rn} data:" . json_encode($this->data, JSON_UNESCAPED_UNICODE);//json_encode($this->data, JSON_UNESCAPED_UNICODE) .
    }
}