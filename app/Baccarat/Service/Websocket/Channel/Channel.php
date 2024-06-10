<?php

namespace App\Baccarat\Service\Websocket\Channel;

use Hyperf\Engine\Contract\ChannelInterface;

class Channel implements ChannelInterface
{
    protected static array $channel = [];

    protected bool $close = false;

    protected int $size;

    public function __construct(int $size = 1)
    {
        $this->size = max(1, $size);
    }

    public function push(mixed $data, float $timeout = -1): bool
    {
        return $this->pushMessage('', $data);
    }

    public function pushMessage(string $key, mixed $value): bool
    {
        if (!isset(static::$channel[$key]) && $this->isWritable() && !$this->isClosing()) {
            static::$channel[$key] = $value;
            return true;
        }
        return false;
    }


    public function pop(float $timeout = -1): mixed
    {
        if (!$this->isEmpty() && !$this->isClosing()) {
            return array_shift(static::$channel);
        }

        return null;
    }

    public function close(): bool
    {
        return $this->close = true;
    }

    public function getCapacity(): int
    {
        return $this->size;
    }

    public function getLength(): int
    {
        return count(static::$channel);
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function hasProducers(): bool
    {
        return true;
    }

    public function hasConsumers(): bool
    {
        return true;
    }

    public function isEmpty(): bool
    {
        return count(static::$channel) === 0;
    }

    public function isFull(): bool
    {
        return count(static::$channel) === $this->size;
    }

    public function isReadable(): bool
    {
        return count(static::$channel) > 0;
    }

    public function isWritable(): bool
    {
        return count(static::$channel) < $this->size;
    }

    public function isClosing(): bool
    {
        return $this->close;
    }

    public function isTimeout(): bool
    {
       return false;
    }


}