<?php

namespace App\Baccarat\Service\Websocket\Message;

use App\Baccarat\Service\Exception\WebSocketTokenExpiredException;
use App\Baccarat\Service\LoggerFactory;
use App\Baccarat\Service\Output\Output;
use App\Baccarat\Service\Websocket\ConnectionPool;
use App\Baccarat\Service\Websocket\WebsocketClient;
use App\Baccarat\Service\Websocket\WebSocketConnectionInterface;
use Hyperf\Coroutine\Locker;
use Hyperf\Coroutine\Parallel;
use Hyperf\Engine\Contract\ChannelInterface;
use PHPUnit\Event\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Notifier;

class MessageProduct
{
    protected readonly Parallel $parallel;

    protected LoggerInterface $logger; // 在类中添加一个logger属性，用于记录日志

    protected array $errors = [];

    public static array $messages = [];

    public function __construct(
        protected readonly ChannelInterface $channel,
        protected readonly ConnectionPool   $connectionPool,
        protected readonly Output           $output,
        protected readonly LoggerFactory    $loggerFactory,
        protected readonly Notifier         $notifier,
        protected readonly int              $size = 5
    )
    {
        $this->logger = $this->loggerFactory->create(); // 在构造函数中初始化日志记录器
    }

    public static function pushMessage(string|int $key, mixed $value): bool
    {
        if (!isset(static::$messages[$key])) {
            static::$messages[$key] = $value;
            return true;
        }
        return false;
    }

    public static function popMessage(string|int $key):mixed
    {
        if ($value = static::$messages[$key] ?? null) {
            unset(static::$messages[$key]);
            return $value;
        }
        return null;
    }

    public function run(): void
    {
        $this->parallel = new Parallel($this->size);

        for ($i = 0; $i < $this->size; $i++) {

            $this->parallel->add(fn() => $this->handleMessageProducers());
        }

        $this->parallel->wait();
    }



    /**
     * @return void
     * @throws WebSocketTokenExpiredException
     * @throws \Throwable
     */
    protected function handleMessageProducers(): void
    {
        try {

            $connection = $this->connectionPool->get();
            if(! $connection instanceof WebSocketConnectionInterface ){
                throw new InvalidArgumentException(sprintf('Connection %s is not a WebSocketConnectionInterface', spl_object_hash($connection)));
            }
            $this->output->warn("Websocket Client handleMessageProducers " . spl_object_hash($connection));
            $WebsocketClients = new WebsocketClient($this->output, $connection, $this->channel);
            $WebsocketClients->startRecvMessage();
        } catch (\Exception|\Throwable $exception) {

            $this->logger->error($exception); // 使用初始化的日志记录器记录错误
            $this->output->error($exception->getMessage());

            if (!in_array($exception->getMessage(), $this->errors)){
                $this->errors[] = $exception->getMessage();
                $notification = new Notification($exception,['wechat_work']);

                $this->notifier->send($notification);
            }
            throw $exception; // 在记录错误后重新抛出异常，以便外部处理
        }
    }
}