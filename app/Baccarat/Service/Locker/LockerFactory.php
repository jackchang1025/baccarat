<?php

namespace App\Baccarat\Service\Locker;

use Hyperf\Redis\RedisFactory;
use InvalidArgumentException;
use Lysice\HyperfRedisLock\LockContract;
use Lysice\HyperfRedisLock\RedisLock;

class LockerFactory
{
    public function __construct(protected RedisFactory $redisFactory)
    {
    }

    public function get(string $name, string $type = '', int $seconds = 20, ?string $owner = null):LockContract
    {
        if ($seconds <= 0) {
            throw new InvalidArgumentException("Seconds must be a positive integer.");
        }

        if ($type === 'redis') {
            return make(RedisLock::class, ['redis' => $this->redisFactory->get('baccarat'), 'name' => $name, 'seconds' => $seconds, 'owner' => $owner]);
        }

        return make(Locker::class, ['name' => $name, 'seconds' => $seconds, 'owner' => $owner]);
    }
}