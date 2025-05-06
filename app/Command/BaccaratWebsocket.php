<?php

declare(strict_types=1);

namespace App\Command;

use App\Baccarat\Service\LoggerFactory;
use App\Baccarat\Service\Output\Output;
use App\Baccarat\Service\Websocket\ConnectionPool;
use App\Baccarat\Service\Websocket\WebsocketClientFactory;
use App\Baccarat\Service\Websocket\WebSocketManageService;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Engine\Channel;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Lysice\HyperfRedisLock\RedisLock;
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
        parent::__construct('baccarat:pool');
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

        $channel = new Channel(5000);

        $websocketClientFactory = new websocketClientFactory(
            channel: $channel,
            host: $this->config->get('websocket.host'),
            token: $this->config->get('websocket.token'),
            connectionTimeout: $this->config->get('websocket.connectionTimeout')
        );

        $ConnectionPool = new ConnectionPool(
            container: $this->container,
            websocketClientFactory: $websocketClientFactory,
            output: make(Output::class),
            config: $this->config->get('websocket.connectionPool'),
        );

        $redis = make(RedisFactory::class)->get('default');

        //使用时间戳避免锁值重复
        $redisLock = new RedisLock($redis, "baccarat:websocket:lock:".time(), 600);

        $reconnectLock = new RedisLock($redis, "baccarat:websocket:reconnect:lock:".time(), 60);

        $WebSocketManageService = new WebSocketManageService(
            websocketClientFactory: $websocketClientFactory,
            connectionPool:$ConnectionPool,
            channel: $channel,
            output: make(Output::class),
            dispatcher: $this->container->get(EventDispatcherInterface::class),
            loggerFactory: $this->container->get(LoggerFactory::class),
            redisLock: $redisLock,
            reconnectLock: $reconnectLock,
            concurrentSize: 10,
            websocketSize: 3
        );

        // 将 WebSocketManageService 实例以 Singleton 模式绑定到容器中
        $this->container->set(WebSocketManageService::class, $WebSocketManageService);

        $WebSocketManageService->run();

        $this->line('Hello Hyperf!', 'info');
    }
}
