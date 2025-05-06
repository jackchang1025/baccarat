<?php
declare(strict_types=1);

/**
 * LotteryResult 类用于处理百家乐游戏中的开奖结果及辅助操作。
 * 此类提供解析开奖字符串、计算手牌点数、判断输赢等功能。
 */

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

    protected ?bool $isNeedDrawCard = NULL;


    public const BACCARAT_TABLE = [
        '3001-77' => '白家乐MX1',
        '3001-78' => '白家乐MX2',
        '3001-79' => '白家乐MX3',
        '3001-80' => '白家乐MX4',
        '3001-81' => '白家乐MX5',
        '3001-88' => '白家乐MX6',
        '3001-89' => '白家乐MX7',
        '3001-90' => '白家乐MX8',
        '3001-91' => '白家乐MX9',


        '3001-82' => '白家乐AS1',
        '3001-83' => '白家乐AS2',
        '3001-84' => '白家乐AS3',
        '3001-85' => '白家乐AS4',
        '3001-86' => '白家乐AS5',
        '3001-87' => '白家乐AS6',

        '3001-57' => '白家乐EU1',
        '3001-58' => '白家乐EU2',
        '3001-59' => '白家乐EU3',
        '3001-60' => '白家乐EU4',
        '3001-61' => '白家乐EU5',

        // '3001-107' => '白家乐RB1',
        // '3001-108' => '白家乐RB2',
        // '3001-109' => '白家乐RB3',
        // '3001-110' => '白家乐RB4',
        // '3001-111' => '白家乐RB5',
        // '3001-112' => '白家乐RB6',
        // '3001-113' => '白家乐RB7',
        // '3001-114' => '白家乐RB8',
        // '3001-115' => '白家乐RB9',
        // '3001-116' => '白家乐RB10',
        
    ];

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


    /**
     * LotteryResult 构造函数
     *
     * @param string      $terrace            平台标识，可能代表使用的游戏桌或平台编号
     * @param string|null $issue              期号，指明当前局的期数
     * @param string|null $result             开奖结果字符串，例如 "S.10,D.1,S.9,C.6"
     * @param string|null $status             当前状态，如 "betting" 或 "waiting"
     * @param string|null $rn                 牌靴信息，格式如 "bb10-25"
     * @param array       $data               额外数据数组，可能包含更多游戏状态信息
     * @param string|null $transformationResult 已转换的结果标识，若为空则根据手牌计算
     * @param array|null  $playerHand         玩家手牌的缓存数组
     * @param array|null  $bankerHand         庄家手牌的缓存数组
     */
    public function __construct(
        public string $terrace,
        public null|string|int $issue = null,
        public ?string $result = null,
        public ?string $status = null,
        public ?string $rn = null,
        public array $data = [],
        protected ?string $transformationResult = null,
        protected ?array $playerHand = null,
        protected ?array $bankerHand = null
    )
    {

    }

    /**
     * 百家乐游戏规则介绍
     *
     * 百家乐是一种比较简单的纸牌游戏，主要分为庄家（Banker）和玩家（Player）两方进行对比。游戏的目标是预测哪一方的点数更接近9点。
     * 
     * 1. 点数计算：
     *    - A 牌算作1点
     *    - 2-9 牌按面值计算
     *    - 10、J、Q、K 牌算作0点
     *    - 如果总点数超过10，则只计算个位数。例如，总点数为15，则算作5点。
     * 
     * 2. 发牌规则：
     *    - 庄家和玩家各发两张牌。
     *    - 如果任一方的总点数为8或9，则称为"天生赢家"，游戏结束，不再补牌。
     * 
     * 3. 补牌规则：
     *    - 如果玩家的总点数为0-5，则玩家需要补一张牌。
     *    - 如果玩家的总点数为6或7，则玩家不需要补牌。
     *    - 庄家的补牌规则较为复杂，取决于庄家和玩家的点数：
     *      - 如果庄家的总点数为0-2，则庄家需要补一张牌。
     *      - 如果庄家的总点数为3，且玩家补的第三张牌不是8，则庄家需要补一张牌。
     *      - 如果庄家的总点数为4，且玩家补的第三张牌是2-7，则庄家需要补一张牌。
     *      - 如果庄家的总点数为5，且玩家补的第三张牌是4-7，则庄家需要补一张牌。
     *      - 如果庄家的总点数为6，且玩家补的第三张牌是6或7，则庄家需要补一张牌。
     *      - 如果庄家的总点数为7，则庄家不需要补牌。
     * 
     * 4. 结果判定：
     *    - 比较庄家和玩家的点数，点数更接近9的一方获胜。
     *    - 如果庄家和玩家的点数相同，则为平局（Tie）。
     * 
     * 判断是否需要补牌（修复版）
     * 
     * @return bool 需要补牌返回 true，否则返回 false
     */
    public function needDrawCard(): bool
    {
        if($this->isNeedDrawCard !== NULL){
            return $this->isNeedDrawCard;
        }

        $playerHand = $this->getPlayerHand();
        $bankerHand = $this->getBankerHand();

        $playerInitial = $this->calculatePoints(array_slice($playerHand, 0, 2));
        $bankerInitial = $this->calculatePoints(array_slice($bankerHand, 0, 2));

        if ($playerInitial >= 8 || $bankerInitial >= 8) {
            return false;
        }

        if(count($bankerHand) > 2){
            return false;
        }

        $playerThird = $this->getThirdCardValue($playerHand);

        $playerNeed = ($playerThird === null) && ($playerInitial <= 5);

        $bankerNeed = match(true) {
            $playerThird === null => $bankerInitial <= 5 && $playerInitial >= 6,
            $bankerInitial <= 2 => true,
            $bankerInitial === 3 => $playerThird !== 8,
            $bankerInitial === 4 => in_array($playerThird, [2,3,4,5,6,7]),
            $bankerInitial === 5 => in_array($playerThird, [4,5,6,7]),
            $bankerInitial === 6 => in_array($playerThird, [6,7]),
            default => false
        };

        $this->isNeedDrawCard = $playerNeed || $bankerNeed;
        return $this->isNeedDrawCard;
    }

    /**
     * 获取玩家第三张牌数值（优化判断）
     */
    private function getThirdCardValue(array $playerHand): ?int
    {
        if (count($playerHand) > 2) {
            $card = $playerHand[2];
            // 使用 explode 分割牌面字符串
            $parts = explode('.', $card);
            $value = end($parts);
            
            $result = match($value) {
                'A' => 1,
                'J', 'Q', 'K' => 0,
                default => (int) $value
            };
            
            return $result >= 10 ? 0 : $result;
        }
        return null;
    }

    public function getTerrainTableName(): ?string
    {
        return self::BACCARAT_TABLE[$this->terrace] ?? null;
    }

    /**
     * 手动设置转换后的开奖结果
     *
     * @param string $transformationResult 转换结果，通常为 PLAYER、BANKER 或 TIE
     */
    public function setTransformationResult(string $transformationResult): void
    {
        $this->transformationResult = $transformationResult;
    }

    /**
     * 通过数组创建 LotteryResult 实例
     *
     * @param string $terrace 平台标识
     * @param array  $data    数组数据，包含开奖信息
     * @return LotteryResult
     */
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

    /**
     * 判断当前状态是否为投注中
     *
     * @return bool 如果状态为 'betting' 则返回 true，否则返回 false
     */
    public function isBetting(): bool
    {
        return $this->status === 'betting';
    }

    /**
     * 判断当前状态是否为等待中
     *
     * @return bool 如果状态为 'waiting' 则返回 true，否则返回 false
     */
    public function isWaiting(): bool
    {
        return $this->status === 'waiting';
    }

    /**
     * 获取当前牌局的牌靴号（第一部分号码）
     *
     * @return string|null 返回牌靴号，如 "10"，如果 rn 为空或格式不正确则返回 null
     */
    public function getDeckNumber(): ?string
    {
        if (empty($this->rn)) {
            return null;
        }
        // 使用正则表达式去除 rn 中的非数字和连字符
        $str = preg_replace('/[^0-9-]/', '', $this->rn);

        // 调用 transform 辅助函数，将格式化后的字符串以连字符分割，返回第一部分作为牌靴号
        return transform($str, fn($str) => explode('-', $str)[0] ?: null);
    }

    /**
     * 获取上一副牌的牌靴号（当前牌靴号减1）
     *
     * @return string|null 返回上一副牌的牌靴号，如 "9"，如果当前牌靴号无效则返回 null
     */
    public function getLastDeckNumber(): ?string
    {
        // 获取当前牌靴号码，并将其转换为整数后减1，最后再转换回字符串
        return transform($this->getDeckNumber(), fn($deckNumber) => (string)(((int)$deckNumber) - 1));
    }

    /**
     * 获取当前开奖结果中牌靴号的第二部分号码
     *
     * @return string|null 返回数字部分，如 "25"，如果格式不正确则返回 null
     */
    public function getNumber(): ?string
    {
        if (empty($this->rn)) {
            return null;
        }
        // 去除 rn 中非数字和连字符的字符
        $str = preg_replace('/[^0-9-]/', '', $this->rn);

        // 使用 transform 辅助函数，分割字符串并取第二部分
        return transform($str, fn($str) => explode('-', $str)[1] ?: null);
    }

    /**
     * 将 LotteryResult 对象转换为数组，通常用于输出或记录日志
     *
     * @return array 数组包含平台、期号、结果、状态及牌靴信息
     */
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

    /**
     * 获取转换后的结果（例如判断出玩家、庄家或平局）
     *
     * 如果手动设置了 transformationResult 则直接返回，
     * 否则根据玩家和庄家的手牌计算结果。
     *
     * @return string|null 返回结果标识：PLAYER, BANKER 或 TIE，如果无法计算则返回 null
     */
    public function getTransformationResult(): ?string
    {
        if($this->transformationResult){
            return $this->transformationResult;
        }

        $playerHand = $this->getPlayerHand();
        $bankerHand = $this->getBankerHand();


        // 如果未手动设置转换结果且手牌有效，则根据手牌计算结果
        return $playerHand && $bankerHand
            ? $this->calculateResult(playerHand: $playerHand, bankerHand: $bankerHand)
            : null;
    }

    /**
     * 根据投注值检查开奖结果的输赢状态
     *
     * - 如果转换后的结果为平局（TIE），返回 BETTING_TIE
     * - 如果投注值与转换结果一致，返回 BETTING_WIN，否则返回 BETTING_LOSE
     *
     * @param string $bettingValue 投注的结果标识
     * @return string 返回投注判断结果：BETTING_WIN, BETTING_LOSE 或 BETTING_TIE
     */
    public function checkLotteryResults(string $bettingValue): string
    {
        $result = $this->getTransformationResult();
        
        if ($result === self::TIE) {
            return self::BETTING_TIE;
        }
        
        return $bettingValue === $result ? self::BETTING_WIN : self::BETTING_LOSE;
    }

    /**
     * 根据指定索引从解析后的结果数组中提取出相应的手牌
     *
     * @param array $resultArray 解析后的数字数组
     * @param array $indexes     要提取的索引集合
     * @return array 返回提取出的手牌数组
     */
    private function parseHand(array $resultArray, array $indexes): array
    {
        return array_values(array_filter($resultArray, fn($key) => in_array($key, $indexes), ARRAY_FILTER_USE_KEY));
    }

    /**
     * 获取玩家的手牌数据
     *
     * @return array|null 如果解析结果有效，则返回玩家手牌数组，否则返回 null
     */
    public function getPlayerHand(): array
    {
        if (!$this->playerHand) {
            $parsed = $this->parseResult();
            $this->playerHand = $parsed ? [
                $parsed[0] ?? null, // 玩家第一张
                $parsed[2] ?? null, // 玩家第二张
                $parsed[4] ?? null  // 玩家第三张（如果有）
            ] : [];
        }
        return array_filter($this->playerHand);
    }

    /**
     * 获取庄家的手牌数据
     *
     * @return array|null 如果解析结果有效，则返回庄家手牌数组，否则返回 null
     */
    public function getBankerHand(): array
    {
        if (!$this->bankerHand) {
            $parsed = $this->parseResult();
            $this->bankerHand = $parsed ? [
                $parsed[1] ?? null, // 庄家第一张
                $parsed[3] ?? null, // 庄家第二张
                $parsed[5] ?? null  // 庄家第三张（如果有）
            ] : [];
        }
        return array_filter($this->bankerHand);
    }

    /**
     * 判断是否为第一局开奖
     *
     * 根据 data 数组中的 'map' 字段，若使用逗号分隔后只有一个有效元素，则视为第一局
     *
     * @return bool 如果为第一局则返回 true，否则返回 false
     */
    public function isFirstLottery(): bool
    {
        if (isset($this->data['map'])) {
            try {
                return count(array_filter(explode(',', $this->data['map']))) === 1;
            } catch (\Exception|\Throwable) {
                return false;
            }
        }
        return false;
    }

    /**
     * 判断是否为第一局（依据号码中的第二部分是否为 "1"）
     *
     * @return bool 如果 getNumber() 返回 "1" 则为第一局，返回 true，否则返回 false
     */
    public function isLotteryOne(): bool
    {
        return $this->getNumber() === '1';
    }

    /**
     * 解析开奖结果字符串，将其转换为数字数组
     *
     * 例如，将 "S.10,D.1,S.9,C.6,," 转换为 [10, 1, 9, 6]
     * 如果解析后的数字数量小于等于 3，则认为结果无效并返回 null
     *
     * @return array|null 返回解析后的数字数组，或 null 表示解析失败
     */
    public function parseResult(): ?array
    {
        if (!$this->result) {
            return null;
        }

        // 分割结果字符串并过滤空值，保留原始牌面
        $resultArray = array_filter(explode(',', $this->result), fn($value) => $value !== '');

        // 验证牌面数量（至少4张初始牌）
        if (count($resultArray) < 4) {
            return null;
        }

        return $resultArray;
    }

    /**
     * 根据玩家和庄家的手牌计算开奖结果
     *
     * 计算每一方的点数（根据规则对 10 取余），点数较高者胜出；若双方点数相等，则为平局
     *
     * @param array $playerHand 玩家手牌数组
     * @param array $bankerHand 庄家手牌数组
     * @return string 返回计算结果标识：PLAYER、BANKER 或 TIE
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
     * 递归查找唯一的 X 坐标，确保与已存在的坐标不冲突
     *
     * @param array $coordinates 已有的坐标数组，每个元素包含 'x' 和 'y'
     * @param int   $x           待检测的 x 值
     * @param int   $y           对应的 y 值
     * @return int 返回一个不冲突的 x 值
     */
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
     * 计算手牌点数（修复版）
     */
    function calculatePoints(array $hand): int
    {
        $points = array_reduce(
            $hand,
            function ($carry, $item) {
                // 提取牌面值（如 S.10 → 10，D.0 → 0）
                $value = substr($item, strpos($item, '.') + 1);
                
                if (in_array($value, ['J','Q','K'])) {
                    return $carry + 0;
                }
                if ($value === 'A') {
                    return $carry + 1;
                }
                
                $num = (int)$value;
                return $carry + ($num >= 10 ? 0 : $num);
            },
            0
        );
        
        return $points % 10;
    }

    /**
     * 判断当前平台是否属于百家乐平台
     *
     * 去除 terrace 中的连字符后，与预定义的百家乐平台列表进行比对
     *
     * @return bool 如果在列表中返回 true，否则返回 false
     */
    public function isBaccarat(): bool
    {
        return in_array(str_replace('-', '', $this->terrace), self::$table);
    }

    /**
     * 判断当前平台是否属于百家乐平台
     *
     * 去除 terrace 中的连字符后，与预定义的百家乐平台列表进行比对
     */
    
    public function isBaccaratTable(): bool
    {
        //判断 $this->terrace 是否在 self::BACCARAT_TABLE 数组的 key 中
        return array_key_exists($this->terrace, self::BACCARAT_TABLE);
    }

    /**
     * 魔术方法，将对象转换为字符串，便于日志打印或调试
     *
     * @return string 返回包含平台、期号、结果、转换结果、状态及其他数据的字符串
     */
    public function __toString(): string
    {
        return "terrace:{$this->getTerrainTableName()} issue:{$this->issue} result:{$this->result} transformationResult:{$this->getTransformationResult()} status:{$this->status} rn:{$this->rn} data:" . json_encode($this->data, JSON_UNESCAPED_UNICODE);
    }
}