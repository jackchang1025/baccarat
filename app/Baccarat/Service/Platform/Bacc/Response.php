<?php

namespace App\Baccarat\Service\Platform\Bacc;

use App\Baccarat\Service\LotteryResult;

class Response
{

    const BANKER = 'BANKER';
    const PLAYER = 'PLAYER';

    const ALMOST = 'almost';
    const HIGH = 'high';
    const MIDDLE = 'medium';

    protected ?string $bets = null;

    protected ?string $credibility = null;

    public function __construct(protected string $message)
    {
        if (!in_array($this->message, ['NOT ENOUGH DATA...', 'NO BET...'])) {
            $data = explode(' ', $this->message);
            if (empty($data) || count($data) !== 3) {
                throw new \InvalidArgumentException("message:{$this->message} Convert Error " . json_encode($data));
            }

            [$this->bets, , $this->credibility] = $data;

            $this->bets = trim($this->bets);
            if (!in_array($this->bets, [self::BANKER, self::PLAYER])) {
                throw new \InvalidArgumentException("bets Convert Error $this->bets");
            }

            $this->credibility = trim($this->credibility);
            if (!in_array($this->credibility, [self::ALMOST, self::HIGH, self::MIDDLE])) {
                throw new \InvalidArgumentException("credibility Convert Error $this->credibility");
            }
        }
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getBets(): ?string
    {
        return $this->bets;
    }

    public function convertBets(): string
    {
        return match ($this->bets) {
            self::BANKER => LotteryResult::BANKER,
            self::PLAYER => LotteryResult::PLAYER,
        };
    }

    public function isBanker(): bool
    {
        return $this->bets === self::BANKER;
    }

    public function isPlayer(): bool
    {
        return $this->bets === self::PLAYER;
    }

    public function getCredibility(): ?string
    {
        return $this->credibility;
    }

    public function isAlmost(): bool
    {
        return $this->credibility === self::ALMOST;
    }

    public function isHigh(): bool
    {
        return $this->credibility === self::HIGH;
    }

    public function isMiddle(): bool
    {
        return $this->credibility === self::MIDDLE;
    }
}