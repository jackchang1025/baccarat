<?php

namespace App\Baccarat\Service\Websocket;

use App\Baccarat\Event\RecvMessageEvent;
use App\Baccarat\Service\LoggerFactory;
use App\Baccarat\Service\Output\Output;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Coroutine\Parallel;
use Hyperf\Engine\Contract\ChannelInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;


class WebSocketManageService
{
    protected ?WebSocketClientInterface $connect = null;

    protected Parallel $coroutineParallel;

    protected LoggerInterface $logger;

    protected bool $running = true;

    public function __construct(
        protected WebsocketClientFactory   $websocketClientFactory,
        protected ConnectionPool   $connectionPool,
        protected ChannelInterface         $channel,
        protected Output                   $output,
        protected EventDispatcherInterface $dispatcher,
        protected LoggerFactory            $loggerFactory,
        protected int                      $concurrentSize = 50,
        protected int                      $websocketSize = 2,
    )
    {
        $this->coroutineParallel = new Parallel($this->concurrentSize);
    }

    public function stop(): void
    {
        $this->running = false;
        $this->channel->close();
    }

    /**
     * 专门的消费协程从 Channel 中获取消息并进行处理。这样可以控制同时处理消息的协程数量,避免过多的协程同时进行数据库操作。
     * 当 WebSocketClientService 类接收消息发生异常时,如果 Channel 不可用,我们应该终止 startMessageConsumers 方法中当前协程的执行,以防止协程继续消费可能无效的数据。
     */
    protected function startMessageConsumers(): void
    {
        for ($i = 0; $i < $this->concurrentSize; $i++) {

            $this->coroutineParallel->add(function (){
                try {

                    while ($this->running && !$this->channel->isClosing()) {

                        $this->handleMessageConsumers();
                    }

                } catch (\Exception $e) {
                    $this->output->error($e->getMessage());
                } finally {
                    $this->output->warn("Pop Message coroutine " . Coroutine::id() . " exit");
                }
            });
        }

        $this->output->warn("pop message coroutine create count:{$this->coroutineParallel->count()}");
    }

    protected function logs(): void
    {
        $this->output->warn("logs coroutine" . Coroutine::id() . " create");

        try {

            while ($this->running) {

                $this->loggerFactory->create('debug', 'baccarat')
                    ->debug("Message Channel Size:{$this->channel->length()} Coroutine Size :{$this->coroutineParallel->count()} Websocket Connect Size:" . $this->connectionPool->getConnectionsInChannel());

                Coroutine::sleep(60); // 避免空轮询
            }

        } catch (\Exception $e) {
            $this->output->error($e->getMessage());
        } finally {
            $this->output->warn("logs  coroutine" . Coroutine::id() . " exit");
        }
    }

    protected function handleMessageConsumers(): void
    {

        $lotteryResult = $this->channel->pop();
        if ($lotteryResult === false) {

            Coroutine::sleep(0.5); // 避免空轮询
            return;
        }

        try {

            $this->dispatcher->dispatch(new RecvMessageEvent($lotteryResult));

        } catch (\Exception|\Throwable $exception) {

            $this->output->error($exception->getMessage());
            $this->loggerFactory->create('debug', 'baccarat')->error($exception, $lotteryResult?->toArray());

            //重新 push
            if (!$this->channel->push($lotteryResult)) {
                // 记录推送失败的日志
                $this->loggerFactory->create('debug', 'baccarat')->warning('Failed to push message back to channel', $lotteryResult->toArray());
            }
        }
    }

    public function run(): void
    {
        $parallel = new Parallel(10);

        $parallel->add(function () {
            $this->startMessageConsumers();
            $this->coroutineParallel->wait();
        });

        $parallel->add(function () {
            $this->logs();
        });

        $parallel->add(function () {
            $this->connectionPool->run();
        });

        $parallel->wait();
    }
}