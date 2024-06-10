<?php

namespace App\Baccarat\Service\BaccaratBetting;

use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;

class BaccaratBettingCache
{
    protected RedisProxy $redisProxy;

    const HASH_NAME = 'baccarat_betting';



    public function __construct(RedisFactory $redisFactory)
    {
        $this->redisProxy = $redisFactory->get('baccarat');
    }

    public function getFormatKey(string $key): string
    {
        return sprintf("%s:%s", self::HASH_NAME, $key);
    }

    public function get(string $key, string $field): CacheData
    {
        $result = $this->redisProxy->Hget($this->getFormatKey($key),$field);
        if ($result) {
            $result = json_decode($result, true);
           return CacheData::make($key, $field, $result);
        }
        return CacheData::make($key, $field);
    }

    public function exists(string $key, string $field): bool
    {
        return $this->redisProxy->hExists($this->getFormatKey($key),$field);
    }

    public function set(string $key, string $field, CacheData $data): bool|int|\Redis
    {
        return $this->redisProxy->hSet($this->getFormatKey($key), $field,json_encode($data->getRules()));
    }

    public function getKeys(string $key): bool|array|\Redis
    {
        return $this->redisProxy->hKeys($this->getFormatKey($key));
    }

    public function del(string $key, string $field): bool|int|\Redis
    {
        return $this->redisProxy->hDel($this->getFormatKey($key), $field);
    }
}