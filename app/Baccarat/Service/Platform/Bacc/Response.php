<?php

namespace App\Baccarat\Service\Platform\Bacc;

use App\Baccarat\Service\LotteryResult;
use InvalidArgumentException;

class Response
{
    // 投注方向常量
    public const BANKER = 'BANKER';
    public const PLAYER = 'PLAYER';
    private const VALID_BETS = [self::BANKER, self::PLAYER];

    // 消息常量
    private const MSG_NOT_ENOUGH_DATA = 'NOT ENOUGH DATA...';
    private const MSG_NO_BET = 'NO BET...';

    // 可信度区间定义
    private const CONFIDENCE_HIGH = 'HIGH';
    private const CONFIDENCE_MEDIUM = 'MEDIUM';
    private const CONFIDENCE_ALMOST = 'ALMOST';

    private readonly string $message;      // 原始消息
    private readonly ?string $bets;        // 投注方向
    private readonly ?int $confidence;     // 可信度数值(0-100)
    private readonly ?string $opposite;    // 对立选项
    private readonly array $percentages;   // 百分比详情

    protected ?string $credibility = null;

    /**
     * @param array $data
     */
    public function __construct(
        private readonly array $data
    ) {
        $this->message     = $data['message'];
        $this->confidence  = $data['confidence'] ?? null;
        $this->opposite    = $data['opposite'] ?? null;
        $this->percentages = $data['percentages'] ?? [];

        $this->validateData();
        $this->bets = $this->extractBetsFromMessage();

        $this->credibility = $this->parseConfidenceFromMessage();
    }

    public function getCredibility(): ?string
    {
        return $this->credibility;
    }

    private function parseConfidenceFromMessage(): ?string
    {
        if (!str_contains($this->message, 'CONFIDENCE:')) {
            return null;
        }

        // 提取 CONFIDENCE: 后面的值
        $parts = explode('CONFIDENCE:', $this->message);

        return !empty($parts[1]) ? strtoupper(trim($parts[1])) : null;
    }

    /**
     * 验证输入数据
     * @throws InvalidArgumentException
     */
    private function validateData(): void
    {
        // 验证confidence范围
        if ($this->confidence !== null && ($this->confidence < 0 || $this->confidence > 100)) {
            throw new InvalidArgumentException("Confidence must be between 0 and 100, got: {$this->confidence}");
        }

        // 验证opposite值
        if ($this->opposite !== null && !in_array($this->opposite, self::VALID_BETS, true)) {
            throw new InvalidArgumentException("Invalid opposite value: {$this->opposite}");
        }

        // 验证percentages
        if (!empty($this->percentages)) {
            if (!isset($this->percentages['banker'], $this->percentages['player'])) {
                throw new InvalidArgumentException("Percentages must contain both banker and player values");
            }
            if ($this->percentages['banker'] + $this->percentages['player'] !== 100) {
                throw new InvalidArgumentException("Percentages must sum to 100");
            }
        }
    }

    /**
     * 从消息中提取投注方向
     */
    private function extractBetsFromMessage(): ?string
    {
        if (str_contains($this->message, self::BANKER)) {
            return self::BANKER;
        }
        if (str_contains($this->message, self::PLAYER)) {
            return self::PLAYER;
        }

        return null;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    public function toArray(): array
    {
        return [
            'message'     => $this->message,
            'confidence'  => $this->confidence,
            'bets'        => $this->bets,
            'opposite'    => $this->opposite,
            'percentages' => $this->percentages,
            'credibility' => $this->credibility,
        ];
    }

    /**
     * 获取原始消息
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * 获取投注方向
     */
    public function getBets(): ?string
    {
        return $this->bets;
    }

    /**
     * 获取可信度数值
     */
    public function getConfidence():null|int|string
    {
        return $this->confidence;
    }

    /**
     * 获取对立选项
     */
    public function getOpposite(): ?string
    {
        return $this->opposite;
    }

    /**
     * 获取百分比详情
     */
    public function getPercentages(): array
    {
        return $this->percentages;
    }

    /**
     * 获取庄家胜率
     */
    public function getBankerPercentage(): ?int
    {
        return $this->percentages['banker'] ?? null;
    }

    /**
     * 获取闲家胜率
     */
    public function getPlayerPercentage(): ?int
    {
        return $this->percentages['player'] ?? null;
    }

    /**
     * 是否投注庄家
     */
    public function isBanker(): bool
    {
        return $this->bets === self::BANKER;
    }

    /**
     * 是否投注闲家
     */
    public function isPlayer(): bool
    {
        return $this->bets === self::PLAYER;
    }

 

    /**
     * 转换为通用投注结果
     */
    public function convertBets(): string
    {
        return match ($this->bets) {
            self::BANKER => LotteryResult::BANKER,
            self::PLAYER => LotteryResult::PLAYER,
            default => throw new \InvalidArgumentException("Invalid bets value: {$this->bets}")
        };
    }

    /**
     * 是否高可信度
     */
    public function isHigh(): bool
    {
        return $this->credibility === self::CONFIDENCE_HIGH;
    }

    /**
     * 是否中等可信度
     */
    public function isMedium(): bool
    {
        return $this->credibility === self::CONFIDENCE_MEDIUM;
    }

    /**
     * 是否较低可信度
     */
    public function isAlmost(): bool
    {
        return $this->credibility === self::CONFIDENCE_ALMOST;
    }

    /**
     * 获取推荐投注方向
     */
    public function getRecommendedBet(): ?string
    {
        if (!$this->isValidBet()) {
            return null;
        }

        return $this->bets;
    }

    /**
     * 是否为有效投注
     */
    public function isValidBet(): bool
    {
        return $this->hasEnoughData() && $this->bets !== null && $this->confidence !== null;
    }

    /**
     * 是否有足够数据做出预测
     */
    public function hasEnoughData(): bool
    {
        return $this->message !== 'NOT ENOUGH DATA...';
    }
}