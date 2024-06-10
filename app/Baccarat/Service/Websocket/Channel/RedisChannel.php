<?php

namespace App\Baccarat\Service\Websocket\Channel;

use App\Baccarat\Service\LotteryResult;
use Hyperf\Engine\Contract\ChannelInterface;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use InvalidArgumentException;
use RuntimeException;

class RedisChannel implements ChannelInterface
{
    protected RedisProxy $redisProxy;

    public function __construct(RedisFactory $redisFactory,protected int $expire = 600,protected string $channelName = 'baccarat_result')
    {
        $this->redisProxy = $redisFactory->get('baccarat');
    }

    /**
     * @param mixed $data
     * @param float $timeout
     * @return bool
     */
    public function push(mixed $data, float $timeout = -1): bool
    {
        if (!$data instanceof LotteryResult) {
            // 使用更具体的异常类型和错误信息
            throw new InvalidArgumentException('push data must be an instance of LotteryResult.');
        }

        try {
            // 验证数据完整性
            if (empty($data->issue) || empty($data->status)) {
                throw new InvalidArgumentException('Invalid issue or status value in LotteryResult.');
            }

            $result = $this->redisProxy->setnx("{$data->issue}:{$data->status}", 1);
            if ($result) {
                $this->redisProxy->expire("{$data->issue}:{$data->status}", $this->expire);
                return $this->redisProxy->rPush($this->channelName, serialize($data));
            }
            return false;
        } catch (\RedisException $e) {
            // 记录详细的错误日志

            // 考虑向上抛出异常或返回更详细的错误信息
            throw new RuntimeException('Redis operation failed.', 0, $e);
        }
    }

    /**
     * @param float $timeout
     * @return mixed
     */
    public function pop(float $timeout = -1): mixed
    {
        try {
            if ($result = $this->redisProxy->lPop($this->channelName)){
                return unserialize($result);
            }
            return false;
        } catch (\RedisException $e) {
            // 记录详细的错误日志

            throw new RuntimeException('Redis operation failed.', 0, $e);
        }
    }

    public function close(): bool
    {
        throw new RuntimeException('Not supported.');
    }

    public function getCapacity(): int
    {
        throw new RuntimeException('Not supported.');
    }

    /**
     * @return int
     * @throws \RedisException
     */
    public function getLength(): int
    {
        return $this->redisProxy->lLen($this->channelName);
    }

    public function isAvailable(): bool
    {
        throw new RuntimeException('Not supported.');
    }

    public function hasProducers(): bool
    {
        throw new RuntimeException('Not supported.');
    }

    public function hasConsumers(): bool
    {
        throw new RuntimeException('Not supported.');
    }

    public function isReadable(): bool
    {
        throw new RuntimeException('Not supported.');
    }

    public function isWritable(): bool
    {
        throw new RuntimeException('Not supported.');
    }

    public function isEmpty(): bool
    {
        return $this->getLength() === 0;
    }

    public function isFull(): bool
    {
        return false;
    }

    public function isClosing(): bool
    {
        return false;
    }

    public function isTimeout(): bool
    {
        return false;
    }
}