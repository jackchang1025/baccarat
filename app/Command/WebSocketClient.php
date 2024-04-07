<?php

declare(strict_types=1);

namespace App\Command;

use App\Baccarat\Service\LoggerFactory;
use App\Baccarat\Service\LotteryResult;
use App\Baccarat\Service\Output\Output as BaccaratOutput;
use App\Baccarat\Service\WebSocketClientService;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\WebSocketClient\ClientFactory;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Runtime;
use function Hyperf\AsyncQueue\dispatch;

#[Command]
class WebSocketClient extends HyperfCommand
{
    public function __construct(
        protected ContainerInterface $container,
        protected ClientFactory $clientFactory,
        protected EventDispatcherInterface $dispatcher,
        protected LoggerFactory $loggerFactory,
        protected BaccaratOutput $baccaratOutput
    )
    {
        parent::__construct('baccarat:websocket');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Hyperf Demo Command');
    }

    public function handle()
    {

         // 此行代码后，文件操作，sleep，Mysqli，PDO，streams等都变成异步IO，见'一键协程化'章节。
         if(Runtime::enableCoroutine() === false){
            $this->error("一键协程化失败");
         }

        //3a086469c0e25b80ab935582378ac92d261ebf54
        $token = "bgfc90ebcc02723b72bac331f43088f574bb845772";

        $url = "wss://fx8ec8.3l3b0um9.com/fxLive/fxLB?gameType=h5multi3";
        $baccarat = new WebSocketClientService(
            output: $this->baccaratOutput,
            clientFactory:new ClientFactory(), 
            dispatcher:$this->dispatcher,
            loggerFactory:$this->loggerFactory,
            host:$url,
            token:$token,
            concurrentSize:100,
            channelSize:1000
        );

        $baccarat->run();
    }
}
