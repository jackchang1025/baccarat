<?php

namespace App\Baccarat\Service\Websocket\Message;

use App\Baccarat\Event\RecvMessageEvent;
use App\Baccarat\Service\LoggerFactory;
use App\Baccarat\Service\Output\Output;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Coroutine\Parallel;
use Hyperf\Engine\Contract\ChannelInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Notifier;

class MessageConsumer
{
    const LOTTERY_TERRACE = '3001-80'; // 使用常量替代硬编码值

    protected readonly Parallel $parallel;

    protected LoggerInterface $logger; // 在类中添加一个logger属性，用于记录日志

    protected array $errors = [];

    public function __construct(
        protected readonly ChannelInterface         $channel,
        protected readonly Output                   $output,
        protected readonly EventDispatcherInterface $dispatcher,
        protected readonly LoggerFactory            $loggerFactory,
        protected readonly Notifier         $notifier,
        protected readonly int $concurrency
    )
    {
        $this->logger = $this->loggerFactory->create(); // 在构造函数中初始化日志记录器
    }

    public function run(): void
    {
        $this->parallel = new Parallel($this->concurrency);

        for ($i = 0; $i < $this->concurrency; $i++) {

            $this->parallel->add(function () {
                $this->handleMessageConsumers();
            });
        }
        $this->parallel->wait();
    }

    /**
     * @return void
     * @throws \Throwable
     */
    protected function handleMessageConsumers(): void
    {
        while (true) {

            try {

                $lotteryResult = $this->channel->pop();
                if (!$lotteryResult) {
                    $this->idleWait(); // 将空闲等待逻辑封装到一个方法中
                    continue;
                }

                if ($lotteryResult->terrace === self::LOTTERY_TERRACE) {
                    $this->output->info($lotteryResult);
                }

                $this->dispatcher->dispatch(new RecvMessageEvent($lotteryResult));

            } catch (\Exception|\Throwable $exception) {

                $this->output->error($exception);
                $this->logger->error($exception); // 使用初始化的日志记录器记录错误

                if (!in_array($exception->getMessage(), $this->errors)){
                    $this->errors[] = $exception->getMessage();
                    $notification = new Notification($exception,['wechat_work']);

                    $this->notifier->send($notification);
                }
                throw $exception; // 在记录错误后重新抛出异常，以便外部处理
            }
        }
    }

    /**
     * 空闲时的等待机制，替代直接的Coroutine::sleep
     */
    protected function idleWait(): void
    {
        // 这里可以实现更高效的等待机制，例如使用事件或信号，本例中保持简单使用sleep
        Coroutine::sleep(0.5);
    }
}