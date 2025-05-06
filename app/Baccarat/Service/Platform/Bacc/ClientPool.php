<?php

namespace App\Baccarat\Service\Platform\Bacc;

use Hyperf\Pool\Pool;
use Hyperf\Pool\SimplePool\PoolFactory;
use GuzzleHttp\Client;

/**
 * HTTP 客户端连接池管理类
 * 负责创建和管理 Guzzle 客户端的连接池
 */
class ClientPool
{
    private Pool $pool;

    /**
     * @param ClientFactory $clientFactory Guzzle 客户端工厂
     * @param PoolFactory $poolFactory 连接池工厂
     * @param int $maxConnections 最大连接数
     * @param int $minConnections 最小连接数
     */
    public function __construct(
        private ClientFactory $clientFactory,
        private PoolFactory $poolFactory,
        int $maxConnections = 200,
        int $minConnections = 10
    ) {
        $this->pool = $this->poolFactory->get('bacc', function() {
            return $this->createClient();
        }, [
            'max_connections' => $maxConnections,
            'min_connections' => $minConnections,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
        ]);
    }

    /**
     * 创建基础 Guzzle 客户端
     * @return Client 配置基本参数的 Guzzle 客户端
     */
    private function createClient(): Client
    {
        return $this->clientFactory->create([
            'base_uri' => 'https://www.bacc.bot',
            'timeout' => 30,
            'connect_timeout' => 30,
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    /**
     * 从连接池获取连接
     * @return \Hyperf\Pool\Connection 连接池连接对象
     */
    public function getConnection(): \Hyperf\Pool\Connection
    {
        return $this->pool->get();
    }
} 