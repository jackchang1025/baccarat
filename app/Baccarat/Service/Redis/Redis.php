<?php

namespace App\Baccarat\Service\Redis;

use App\Baccarat\Service\LoggerFactory;
use Hyperf\Collection\Collection;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\SerializerInterface;

class Redis
{
    protected RedisProxy $redis;

    protected LoggerInterface $logger;

    public function __construct(protected RedisFactory $redisFactory,protected LoggerFactory $loggerFactory,protected string $format = "%s:%s")
    {
        $this->redis = $this->redisFactory->get('baccarat');
        $this->logger = $this->loggerFactory->get('redis','baccarat');
    }

    /**
     * @param string $table
     * @param string $date
     * @param string $format
     * @return Collection|null
     * @throws \RedisException
     */
    public function hGetAll(string $table, string $date, string $format = 'json'): ?Collection
    {
        $result =  $this->redis->hGetAll($table);

        $collection = new Collection();

        if (is_array($result) && !empty($result)){

            return array_reduce($result, function (Collection $collection,string $value) use ($format){

                filled($data = $this->decode($value,$format)) && $collection->push($data);

                return $collection;
            },$collection);
        }
        return $collection;
    }


    /**
     * @param string $table
     * @param string $key
     * @param string $value
     * @return bool|int|\Redis
     * @throws \RedisException
     */
    public function hSetnx(string $table, string $key, string $value): bool|int|\Redis
    {
        return $this->redis->hSetNx($table, $key, $value);
    }

    public function hSet(string $table, string $key, string $value)
    {

    }

    /**
     * @param string $terraceId
     * @param string $date
     * @param string $shoesId
     * @return bool
     * @throws \RedisException
     */
    public function hExists(string $terraceId, string $date, string $shoesId): bool
    {
        return $this->redis->hExists($this->getFormatTable($terraceId, $date), $shoesId);
    }

    /**
     * @param string $table
     * @param string $key
     * @param string $format
     * @return array|null
     * @throws \RedisException
     */
    public function hGet(string $table, string $key,string $format = 'json'): ?array
    {
        $data = $this->redis->hGet($table,$key);

        // 解压缩数据
        return $data !== false ? $this->decode($data,$format) : null;
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
            $result = $this->redis->hScan($this->getFormatTable($terraceId, $date), $cursor, $count);

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