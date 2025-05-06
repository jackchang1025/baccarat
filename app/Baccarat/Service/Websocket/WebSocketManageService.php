<?php

namespace App\Baccarat\Service\Websocket;

use App\Baccarat\Event\RecvMessageEvent;
use App\Baccarat\Service\LoggerFactory;
use App\Baccarat\Service\Output\Output;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Coroutine\Parallel;
use Hyperf\Engine\Contract\ChannelInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use App\Baccarat\Service\LotteryResult;
use Lysice\HyperfRedisLock\RedisLock;

class WebSocketManageService
{
    protected ?WebsocketConnection $connection = null;
    protected Parallel $parallel;

    protected int $currentConnections = 0;


    public function __construct(
        protected WebsocketClientFactory $websocketClientFactory,
        protected ConnectionPool $connectionPool,
        protected ChannelInterface $channel,
        protected Output $output,
        protected EventDispatcherInterface $dispatcher,
        protected LoggerFactory $loggerFactory,
        protected RedisLock $redisLock,
        protected RedisLock $reconnectLock,
        protected int $concurrentSize = 10,
        protected int $websocketSize = 3,
    ) {
        $this->parallel = new Parallel($this->concurrentSize + $this->websocketSize);
    }

    public function run(): void
    {
        $this->startMessageConsumers();

        $this->startWebsocketDaemon();

        $this->parallel->wait();
    }

    protected function startMessageConsumers(): void
    {
        for ($i = 0; $i < $this->concurrentSize; $i++) {
            $this->parallel->add(function () {
                try {
                    while (!$this->channel->isClosing()) {
                        $this->processChannelMessage();
                    }
                } finally {
                    $this->output->warn("Message consumer exiting: ".Coroutine::id());
                }
            });
        }
    }

    private function processChannelMessage(): void
    {
        $message = $this->channel->pop();
        if ($message === false) {
            Coroutine::sleep(0.1);
            return;
        }

        try {
            $this->dispatcher->dispatch(new RecvMessageEvent($message));
        } catch (\Throwable $e) {
            $this->handleFailedMessage($message, $e);
        }
    }

    private function handleFailedMessage(mixed $message, \Throwable $e): void
    {
        $this->output->error("Message processing failed: ".$e->getMessage());
        $this->loggerFactory->get('debug', 'baccarat')->error($e->getMessage(), [
            'message' => $message instanceof LotteryResult ? $message->toArray() : $message
        ]);

        if (!$this->channel->push($message)) {
            $this->loggerFactory->get('debug', 'baccarat')->warning('Message requeue failed', [
                'message' => $message instanceof LotteryResult ? $message->toArray() : $message
            ]);
        }
    }

    protected function startWebsocketDaemon(): void
    {
        $this->parallel->add(function ()  {
            // 初始延迟为0
            $initialDelay = 0;
            // 每个协程间隔10秒
            $intervalDelay = 10;

            while (!$this->channel->isClosing()) {
                if($this->currentConnections < $this->websocketSize) {
                    Coroutine::create(function () use (&$initialDelay, $intervalDelay) {
                        try {
                            $this->currentConnections++;
                            
                            // 使用当前累积的延迟时间
                            Coroutine::sleep($initialDelay);
                            
                            // 执行消息接收
                            $this->recvMessage();
                            
                        } finally {
                            $this->currentConnections--;
                        }
                    });

                    // 增加下一个协程的延迟时间
                    $initialDelay += $intervalDelay;
                    
                    // 如果延迟时间超过了最大协程数 * 间隔时间，则重置为0
                    if ($initialDelay >= $this->websocketSize * $intervalDelay) {
                        $initialDelay = 0;
                    }
                }

                Coroutine::sleep(1);
            }
        });
    }

    public function recvMessage(): void
    {
        $lock = false;

        /**
         * @var WebsocketConnectionInterface $connection
         */
        $connection = $this->connectionPool->get();

        try {

            $this->logConnectionState(Coroutine::id(), 'created');

            while (true) {

                $this->maintainConnection($connection, $lock);

                $message = $connection->retryRecvMessage();

                $this->processIncomingMessage($connection, $message, $lock);

                Coroutine::sleep(0.1);
            }

        }catch (\Throwable $e) {


        } finally {

            $this->output->error("Critical error: ".$e->getMessage());
            $lock && $this->redisLock->release();

            $this->logConnectionState(Coroutine::id(), 'closed');
        }
    }

    private function maintainConnection(WebsocketConnectionInterface $connection, bool &$lock): void
    {
        if ($connection->check()) {
            $this->performReconnection($connection, $lock);
        }
    }

    private function performReconnection(WebsocketConnectionInterface $connection, bool &$lock): void
    {
       
        $reconnectLock = null;
        try {

            // 获取重连锁
            $reconnectLock = $this->reconnectLock->acquire();
            if(!$reconnectLock) {
                return;
            }

            $this->output->warn("Acquired reconnect lock. Reconnecting...");

            // 如果持有消息锁则先释放
            if ($lock) {
                $this->redisLock->release();
                $lock = false;
                $this->output->warn("Released message lock for reconnection...");
            }

            // 重连
            $connection->reconnect();

        } catch (\Throwable $e) {

            $this->output->error("Reconnection failed: {$e->getMessage()}...");
        } finally {

            if($reconnectLock) {
                $this->reconnectLock->release();
                $this->output->warn("Released reconnect lock...");
            }
        }
    }

    private function processIncomingMessage(WebsocketConnectionInterface $connection, mixed $message, bool &$lock): void
    {
        if ($connection->isPing()) {
            $connection->push($message);
            return;
        }

        if ($connection->isOnUpdateGameInfo()) {
            $this->handleGameUpdate($message, $lock);
        }
    }

    private function handleGameUpdate(array $message, bool &$lock): void
    {
        if (!$lock) {
            $lock = $this->acquireLock();
        }

        if ($lock) {
            $this->processGameData($message['sl'] ?? []);
        }
    }

    private function acquireLock(): bool
    {
        if ($this->redisLock->acquire()) {
            $this->output->warn("Lock acquired in coroutine: ".Coroutine::id());
            return true;
        }
        return false;
    }

    private function processGameData(array $gameData): void
    {
        foreach ($gameData as $terrace => $item) {
            $lotteryResult = LotteryResult::fromArray($terrace, $item);
            if ($lotteryResult->isBaccaratTable()) {
                $this->channel->push($lotteryResult);
            }
        }
    }

    private function logConnectionState(int $coroutineId, string $state): void
    {
        $this->output->warn(sprintf(
            "Connection %s in coroutine #%d (Active: %d/%d)",
            $state,
            $coroutineId,
            $this->currentConnections,
            $this->websocketSize
        ));
    }
}