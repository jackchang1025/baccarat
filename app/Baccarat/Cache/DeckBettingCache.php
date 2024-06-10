<?php

namespace App\Baccarat\Cache;

use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Psr\SimpleCache\CacheInterface;

class DeckBettingCache
{

    const key = 'terrace_deck_betting:';

    protected RedisProxy $redisProxy;

    public function __construct(protected RedisFactory $factory)
    {
        $this->redisProxy = $this->factory->get('default');
    }

    public function set(int $deckId,mixed $value = true,null|int|\DateInterval $ttl = 60 * 60): bool|\Redis
    {
        return$this->redisProxy->set($this->sprintf($deckId), $value,$ttl);
    }

    public function get(int $deckId): mixed
    {
        return $this->redisProxy->get($this->sprintf($deckId));
    }

    protected function sprintf(string $key): string
    {
        return sprintf('%s%s', self::key, $key);
    }
}