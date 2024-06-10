<?php

declare(strict_types=1);

namespace App\Command;

use App\Baccarat\Service\LoggerFactory;
use App\Baccarat\Service\Output\Output;
use App\Baccarat\Service\Websocket\ConnectionPool;
use App\Baccarat\Service\Websocket\WebSocketManageService;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\RedisFactory;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Runtime;

#[Command]
class BaccaratWebsocket extends HyperfCommand
{
    public function __construct(
        protected ContainerInterface $container,
        protected ConfigInterface $config,
        protected RedisFactory $redisFactory,
    )
    {
        parent::__construct('baccarat:websocketPool');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');
    }

    public function handle()
    {
        if (!$this->config->get('websocket')){
            throw new \Exception('websocket config is not exist');
        }

        // 此行代码后，文件操作，sleep，Mysqli，PDO，streams等都变成异步IO，见'一键协程化'章节。
        if(Runtime::enableCoroutine() === false){
            $this->error("一键协程化失败");
            return;
        }

        $connectionPool = new ConnectionPool(
            container: $this->container,
            output: make(Output::class),
            connectionCreationIntervalTime: $this->config->get('websocket.connectionPool.connection_creation_interval_time'),
            connectionCheckIntervalTime: $this->config->get('websocket.connectionPool.connection_check_interval_time'),
            config: $this->config->get('websocket.connectionPool'),
        );

        $WebSocketManageService = new WebSocketManageService(
            connectionPool:$connectionPool,
            output: make(Output::class),
            dispatcher: $this->container->get(EventDispatcherInterface::class),
            loggerFactory: $this->container->get(LoggerFactory::class),
            channel: new \App\Baccarat\Service\Websocket\Channel\Channel(10000)
        );

        $WebSocketManageService->run();

        $this->line('Hello Hyperf!', 'info');
    }
}
