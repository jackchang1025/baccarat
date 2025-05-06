<?php

namespace App\Baccarat\Service\Websocket;

use App\Baccarat\Service\LoggerFactory;
use App\Baccarat\Service\Output\Output;
use Hyperf\Contract\ConnectionInterface;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Coroutine\Parallel;
use Psr\Log\LoggerInterface;
use Hyperf\Pool\Pool;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class ConnectionPool extends Pool
{

    public function __construct(
        protected ContainerInterface     $container,
        protected WebsocketClientFactory $websocketClientFactory,
        protected Output $output,
        array                            $config = [],
    )
    {
        parent::__construct($container, $config);
    }

    /**
     * @return ConnectionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function createConnection(): ConnectionInterface
    {
        try {

            return $this->websocketClientFactory->create();

        } catch (\Exception $e) {
            // 记录日志或采取其他措施
            $this->container->get(LoggerInterface::class)->error('Failed to create connection: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function initConnections(): void
    {

        if($this->option->getMinConnections() - $this->getConnectionsInChannel()){

            $this->output->warn("create Websocket Client Coroutine id:". Coroutine::id());

            $this->release($this->createConnection());
        }
    }

    protected function pop(): ConnectionInterface|bool
    {
        return $this->channel->pop($this->option->getWaitTimeout());
    }

    public function checkConnections(): void
    {
        try {

            $this->output->warn("create Websocket Client check Coroutine id:". Coroutine::id());

            while (true) {

                $this->initConnections();

                $num = $this->getConnectionsInChannel();

                //从连接池获取连接并检查过期事件否则重连
                for ($i = 0; $i < $num; $i++) {

                    // 从连接池获取连接并检查过期事件否则重连
                    /** @var WebsocketConnectionInterface $connection */
                    if (($connection = $this->pop()) && $this->getConnectionsInChannel() < $this->option->getMaxConnections()) {

                        // 将过期的连重连
                        if($connection->check()){
                            $connection->reconnect();

                            $this->output->warn("Websocket Client reconnect Coroutine id:". Coroutine::id(). 'status:'.$connection->isAuthenticated());
                        }

                        //将连接释放回连接池
                        $this->release($connection);
                    }
                }
                Coroutine::sleep(5); // 每5秒检查一次
            }
        } catch (\Exception $e) {

            $this->container->get(LoggerFactory::class)->get('baccarat')->error('Failed to check connection: ' . $e->getMessage());
        } finally {
            $this->output->warn("Websocket Client check Coroutine Exit id:". Coroutine::id());
        }
    }
}