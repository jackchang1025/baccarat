<?php

namespace App\Baccarat\Service\Redis;

use App\Baccarat\Service\LoggerFactory;
use Hyperf\Collection\Collection;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Psr\Log\LoggerInterface;

class LotteryResultService
{

    protected RedisProxy $redis;

    protected LoggerInterface $logger;

    public function __construct(protected RedisFactory $redisFactory,protected LoggerFactory $loggerFactory,protected string $format = "%s:%s")
    {
        $this->redis = $this->redisFactory->get('default');
        $this->logger = $this->loggerFactory->get('redis','baccarat');
    }

    public function getFormat(string $terraceId, string $date): string
    {
        return sprintf($this->format, $terraceId, $date);
    }

    public function encode(array $data):string
    {
        try {
            return json_encode($data);
        } catch (\JsonException $e) {
            return '';
        }
    }

    public function decode(string $data):array
    {
        try {
            return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }
    }

    public function hGetAll(string $terraceId, string $date): ?Collection
    {
        try {
            $result =  $this->redis->hGetAll($this->getFormat($terraceId, $date));

            $collection = new Collection();

            if (is_array($result) && !empty($result)){

                return array_reduce($result, function (Collection $collection,string $value){

                    filled($data = $this->decode($value)) && $collection->push($data);

                    return $collection;
                },$collection);
            }
            return $collection;

        } catch (\RedisException $e) {

            $this->logger->error($e->getMessage());
            return null;
        }
    }


    public function hSetnx(string $terraceId, string $date, string $shoesId, array $lotteryData, int $expire = 0): bool|int|\Redis
    {
        // 使用台号、牌靴和日期构建 Redis 的 key
        $redisKey = $this->getFormat($terraceId, $date);

        // 将压缩后的数据存储到 Redis 的 Hash 类型中
        try {

            $result = $this->redis->hSetNx($redisKey, $shoesId, $this->encode($lotteryData));

            // 设置数据的过期时间,这里假设数据保留 30 天
            if ($expire > 0){
                $this->redis->expire($redisKey, 86400 * 30);
            }

            return $result;

        } catch (\RedisException $e) {

            $this->logger->error($e->getMessage());
            return false;
        }
    }

    public function hExists(string $terraceId, string $date, string $shoesId): bool
    {

        // 检查 Redis 中是否已经存在相应的数据
        try {
            if ($this->redis->hExists($this->getFormat($terraceId, $date), $shoesId)) {
                return true;
            }
        } catch (\RedisException $e) {
            $this->logger->error($e->getMessage());
        }
        return false;
    }

    /**
     * @param string $terraceId
     * @param string $shoesId
     * @param string $date
     * @return array|null
     */
    public function hGet(string $terraceId, string $date,string $shoesId): ?array
    {
        try {

            $compressedData = $this->redis->hGet($this->getFormat($terraceId, $date), $shoesId);

            // 解压缩数据
            return $compressedData !== false ? $this->decode($compressedData) : null;

        } catch (\RedisException|\JsonException $e) {

            $this->logger->error($e->getMessage());
            return null;
        }
    }

    /**
     * 迭代哈希表中的键值对
     *
     * @param string $terraceId
     * @param string $date
     * @param callable $callback
     * @return void
     */
    public function iterateLotteryResults(string $terraceId, string $date, callable $callback): void
    {

        $cursor = 0;
        $count = 100;

        do {
            // 使用 hScan 方法迭代哈希表
            $result = $this->redis->hScan($this->getFormat($terraceId, $date), $cursor, $count);

            if ($result === false || empty($result[1])) {
                break;
            }

            $cursor = $result[0];
            $lotteryResults = $result[1];

            // 解码每个牌靴的开奖数据并调用回调函数进行处理
            foreach ($lotteryResults as $shoesId => $encodedData) {
                $decodedData = $this->decode($encodedData);
                $callback($terraceId, $date, (int)$shoesId, $decodedData);
            }
        } while ($cursor !== 0);
    }

    public function deleteExpiredLotteryResults(string $terraceId, string $date): void
    {
        // 使用台号和日期构建 Redis 的 key 前缀
        $redisKeyPrefix = sprintf("%s:*", $terraceId);

        // 使用 Redis 的 scan 命令获取所有匹配的 key
        $iterator = null;

        while (true) {
            $result = $this->redis->scan($iterator, $redisKeyPrefix, 100);

            if ($result === false || empty($result[1])) {
                break;
            }
            $iterator = $result[0];
            $keys = $result[1];

            foreach ($keys as $key) {
                try {
                    $this->redis->hDel($key, $date);
                } catch (\RedisException $e) {
                    $this->logger->error($e->getMessage());
                }
            }

            if ($iterator === 0) {
                break;
            }
        }
    }
}