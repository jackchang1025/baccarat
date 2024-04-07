<?php

namespace App\Baccarat\Service\Websocket;

use App\Baccarat\Service\Exception\WebSocketRunException;
use App\Baccarat\Service\Exception\WebSocketTimeOutException;
use App\Baccarat\Service\Exception\WebSocketTokenExpiredException;
use App\Baccarat\Service\LotteryResult;
use App\Baccarat\Service\Output\Output;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Engine\Contract\ChannelInterface;
use Hyperf\Stringable\Str;
use Hyperf\WebSocketClient\Client;
use Hyperf\WebSocketClient\ClientFactory;
use Lysice\HyperfRedisLock\RedisLock;


class WebsocketClient implements WebSocketClientInterface
{
    protected ?Client $client = null;

    protected array $message = [];
    protected int $createdAt = 0;

    protected bool $isLock = false;

    protected bool $recvMessage = false;

    /**
     * @param ClientFactory $clientFactory
     * @param Output $output
     * @param RedisLock $redisLock
     * @param ChannelInterface $channel
     * @param ConfigInterface $config
     * @param string $host
     * @param string $token
     * @param int $connectionTimeout
     */
    public function __construct(
        protected ClientFactory $clientFactory,
        protected Output $output,
        protected RedisLock $redisLock,
        protected ChannelInterface $channel,
        protected ConfigInterface $config,
        protected string        $host,
        protected string        $token,
        protected int           $connectionTimeout = 600,
    )
    {
        $this->reconnect();

//        $this->startRecvMessageWithCoroutine();
    }

    public function startRecvMessageWithCoroutine(): void
    {
        Coroutine::create(fn() => $this->startRecvMessage());
    }

    /**
     * @return void
     * @throws WebSocketRunException
     * @throws WebSocketTimeOutException
     * @throws WebSocketTokenExpiredException
     * @throws \Throwable
     */
    protected function startRecvMessage(): void
    {
        $this->output->info("create Coroutine recv message Coroutine id:". Coroutine::id());

        try {

            while (true) {

                if (!$this->recvMessage){
                    Coroutine::sleep(0.5);
                    continue;
                }
                if ($this->client === null) {
                    throw new WebSocketRunException('client is null');
                }

                $this->retryRecvMessage();

                $this->handleAction();

//                $this->output->info("recv message: ".json_encode($this->message));

                if ($this->checkLock() && $this->isOnUpdateGameInfo()) {
                    $this->pushMessage();
                }
            }

        } catch (\Exception|\Throwable $e) {

            $this->output->error($e->getMessage());

            throw $e;
        }finally {
            // 无论是否发生异常,都执行关闭连接和释放锁的操作
            $this->close();
            $this->output->info("Recv Message coroutine exit Coroutine id:" . Coroutine::id());
            $this->redisLock->release();
            $this->output->warn('releaseLock:'.(int)  $this->isLock);
        }
    }

    public function pushMessage(): void
    {
        foreach ($this->message['sl'] as $terrace => $item) {

            $lotteryResult = LotteryResult::fromArray($terrace,$item);

            $lotteryResult->isBaccarat() && $this->channel->push($lotteryResult);
        }
    }

    /**
     * @return bool
     */
    public function checkLock(): bool
    {
        return $this->Lock() && $this->releaseLock();
    }

    /**
     * 如果 isLock 为 false 且 check 为 false 则获取锁
     * @return bool
     */
    public function Lock(): bool
    {
        if (!$this->isLock && !$this->check()){
            if ($this->isLock = $this->redisLock->acquire()){
                $this->output->warn("acquire():".(int)  $this->isLock);
            }
        }
        return $this->isLock;
    }

    /**
     * 如果 isLock 为 true 且 check 为 true 则释放锁
     * @return bool
     */
    public function releaseLock(): bool
    {
        if ($this->isLock && $this->check()){
            $this->redisLock->release();
            $this->isLock = false;
            $this->output->warn('releaseLock:'.(int)  $this->isLock);
        }
        return $this->isLock;
    }

    public function getMessage(): array
    {
        return $this->message;
    }

    public function push(array $data, int $opcode = WEBSOCKET_OPCODE_TEXT, ?int $flags = null): bool
    {
        return $this->client->push(json_encode($data), $opcode, $flags);
    }

    protected function decodeMessage(string $message): array
    {
        try {
            return json_decode($message, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }
    }


    /**
     * @param int $timeout
     * @return array
     * @throws WebSocketTimeOutException
     * @throws WebSocketTokenExpiredException
     */
    protected function recv(int $timeout = 10): array
    {
        try {
            $msg = $this->client->recv($timeout);
            if (!$msg) {
                throw new WebSocketTimeOutException('接收消息失败或超时');
            }

            $this->message = $this->decodeMessage($msg->data);

            if (empty($this->message)) {
                throw new WebSocketTimeOutException('消息格式错误');
            }

            if ($this->isTimeoutError()) {
                throw new WebSocketTimeOutException('Time Out NetConnection Connect Closed');
            }

            if ($this->isRrnError()) {
                throw new WebSocketTokenExpiredException("runEor {$this->message['runEor']}" . json_encode($this->message));
            }

            return $this->message;
        } catch (\Exception $e) {
            // 记录日志
            $this->output->error('接收消息失败: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @return array
     * @throws WebSocketTimeOutException
     * @throws WebSocketTokenExpiredException
     */
    protected function retryRecvMessage(): array
    {
        $maxRetries = 3;
        $retryInterval = 1;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                return $this->recv();
            } catch (WebSocketTimeOutException $e) {
                if ($attempt === $maxRetries) {
                    throw $e;
                }

                $this->reconnect();
                $this->output->warn('重试接收消息: ' . $attempt);
                Coroutine::sleep($retryInterval);
            }
        }

        throw new WebSocketTimeOutException('重试接收消息失败');
    }

    protected function isRrnError(): bool
    {
        return isset($this->message['runEor']) && $this->message['runEor'] === 'API_EC_ACC_SID_INVALID';
    }

    protected function isTimeoutError(): bool
    {
        return isset($this->message['NetStatusEvent']) && $this->message['NetStatusEvent'] === 'NetConnection.Connect.Closed';
    }


    public function handleAction(): void
    {
         match (true) {
            $this->isPing() => $this->push($this->message),
            $this->isReady() => $this->login(),
            $this->isOnHallLogin() => $this->handLogin(),
            default => null
        };
    }

    public function login(): bool
    {
        return $this->push($this->config->get('websocket.login'));
    }

    public function handLogin(): bool
    {
        return $this->push($this->config->get('websocket.handLogin'));
    }

    public function getAction(): ?string
    {
        return $this->message['action'] ?? null;
    }

    public function isOnActivity(): bool
    {
        return $this->getAction() === 'onActivity';
    }

    public function isOnUpdateGameInfo(): bool
    {
        return $this->getAction() === 'onUpdateGameInfo';
    }

    public function isPing(): string|int|bool
    {
        return $this->message['ping'] ?? false;
    }

    public function isReady(): bool
    {
        return $this->getAction() === 'ready';
    }

    public function isOnHallLogin(): bool
    {
        return $this->getAction() === 'onHallLogin';
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    /**
     * 判断连接是否超时
     * @return bool
     */
    public function isTimeOut(): bool
    {
        return time() - $this->createdAt >= $this->connectionTimeout;
    }

    public function close(): bool
    {
        return $this->client->close();
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * 获取剩余超时时间
     * @return int
     */
    public function getRemainingTimeOut(): int
    {
        return max(0, $this->connectionTimeout - (time() - $this->createdAt));
    }

    public function getConnection(): static
    {
        return $this;
    }

    public function reconnect(): bool
    {
        $this->recvMessage = false;
        $this->output->warn("reconnect");
        $this->createdAt = time();
        $this->client = $this->clientFactory->create($this->host);
        $this->recvMessage = true;
        return (bool) $this->client;
    }

    public function check(): bool
    {
        return $this->isTimeOut() || $this->getRemainingTimeOut() <= $this->config->get('websocket.remainingTimeOut');
    }

    public function getRemainingTime():int
    {
        return max(0, $this->getRemainingTimeOut() - $this->config->get('websocket.remainingTimeOut'));;
    }

    public function release(): void
    {
        // TODO: Implement release() method.
    }
}