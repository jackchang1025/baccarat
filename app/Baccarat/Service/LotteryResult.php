<?php

namespace App\Baccarat\Service;


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
        "30471" => "A",
        "30474" => "B",
        "30011" => "百家乐A",
        "30012" => "百家乐B",
        "30013" => "百家乐C",
        "30016" => "百家乐D",
        "30017" => "百家乐E",
        "300136" => "百家乐I",
        "300137" => "百家乐J",
        "300157" => "百家乐EU1",
        "300158" => "百家乐EU2",
        "300159" => "百家乐EU3",
        "300160" => "百家乐EU4",
        "300161" => "百家乐EU5",
        "300182" => "百家乐AS1",
        "300183" => "百家乐AS2",
        "300184" => "百家乐AS3",
        "300185" => "百家乐AS4",
        "300186" => "百家乐AS5",
        "300187" => "百家乐AS6",
        "300177" => '百家乐MX1',
        "300178" => '百家乐mx2',
        "300179" => '百家乐mx3',
        "300180" => '百家乐mx4',
        "300181" => '百家乐mx5',
        "300188" => '百家乐MX6',
        "300189" => '百家乐MX7',
        "300190" => '百家乐MX8',
        //区块链百家
        "30431" => "百家乐BC1",
        "30432" => "百家乐BC2",
        "30433" => "百家乐BC3",
        "30434" => "百家乐BC4",
        "30435" => "百家乐BC5",
        "30436" => "百家乐BC6",

        //百家乐
        "30251" => "百家乐BC1",
        "30252" => "百家乐BC2",
        "30253" => "百家乐BC3",
        "30254" => "百家乐BC4",
        "30255" => "百家乐BC5",
        "30256" => "百家乐BC6",
        "30257" => "百家乐BC7",

        //保险百家乐
        "30271" => "保险百家乐BC1",
        "30272" => "保险百家乐BC2",
        "30273" => "保险百家乐BC3",

        //金臂百家
        "3001107" => "百家乐RB1",
        "3001108" => "百家乐RB2",
        "3001109" => "百家乐RB3",
        "3001110" => "百家乐RB4",
        "3001111" => "百家乐RB5",
        "3001112" => "百家乐RB6",
        "3001113" => "百家乐RB7",
        "3001114" => "百家乐RB8",
        "3001115" => "百家乐RB9",
        "3001116" => "百家乐RB10",
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

    public function getTerraceName(): ?string
    {
        return self::$table[str_replace('-', '', $this->terrace)] ?? null;
    }

    public function getTerrace(): string
    {
        return $this->terrace;
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
        return explode('-', $str)[0] ?: null;
    }

    /**
     * 获取下一张牌靴的牌靴号
     * @return string|null
     */
    public function getLastDeckNumber(): ?string
    {
        $deckNumber = $this->getDeckNumber();
        if ($deckNumber === null) {
            return null;
        }
        //首先获取当前牌靴号，然后转换为数字类型 并且加1，最后转换为字符串类型
        return (string)((int)$deckNumber - 1);
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
        return explode('-', $str)[1] ?: null;
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
        if ($this->getTransformationResult() === self::TIE) {
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
        if (isset($this->data['map']) && is_string($this->data['map'])) {
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
            default => self::TIE,
        };
    }

    /**
     * 计算手牌点数
     * @param array $hand
     * @return int
     */
    function calculatePoints(array $hand): int
    {
        $points = array_reduce($hand, function (int $carry, int $item) {

            $item = $item >= 10 ? 0 : $item;

            return $carry + $item;
        }, 0);

        return $points % 10;
    }

    public function isBaccarat(): bool
    {
        return in_array(str_replace('-', '', $this->terrace), array_keys(self::$table));
    }

    public function __toString(): string
    {
        return "terrace:{$this->terrace} issue:{$this->issue} result:{$this->result} transformationResult:{$this->getTransformationResult()} status:$this->status rn:{$this->rn} data:" . json_encode($this->data, JSON_UNESCAPED_UNICODE);//json_encode($this->data, JSON_UNESCAPED_UNICODE) .
    }
}