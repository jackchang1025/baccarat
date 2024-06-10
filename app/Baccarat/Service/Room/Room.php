<?php

namespace App\Baccarat\Service\Room;
class Room
{
    public function __construct(
        protected string $bettingId,
        protected string $terraceId,
        protected string $deckId,
        protected int $createTime,
        protected int $seconds = 60
    )
    {
    }

    public function getBettingId(): string
    {
        return $this->bettingId;
    }

    public function getTerraceId(): string
    {
        return $this->terraceId;
    }

    public function getDeckId(): string
    {
        return $this->deckId;
    }

    public function getSeconds(): int
    {
        return $this->seconds;
    }

    public function getCreateTime(): int
    {
        return $this->createTime;
    }

    /**
     * 判断当前房间数据是否与给定结果一致
     * @param string $deckId
     * @return bool
     */
    public function checkRoom(string $deckId): bool
    {
        return $deckId === $this->deckId;
    }

    public function isRoomExpired(): bool
    {
        return time() - $this->createTime >= $this->seconds;
    }

    public function touch(): self
    {
        $this->createTime = time();
        return $this;
    }
}