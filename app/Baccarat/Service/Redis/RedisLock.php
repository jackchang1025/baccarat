<?php

namespace App\Baccarat\Service\Redis;

use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;

class RedisLock extends \Lysice\HyperfRedisLock\RedisLock
{
    public function __construct(protected RedisFactory $redisFactory,string $name, int $seconds, mixed $owner = null)
    {
        parent::__construct($this->redisFactory->get('baccarat'), $name, $seconds, $owner);
    }

    public static function make(string $name, int $seconds, mixed $owner = null): static
    {
        return new static(make(RedisFactory::class), $name, $seconds, $owner);
    }
}