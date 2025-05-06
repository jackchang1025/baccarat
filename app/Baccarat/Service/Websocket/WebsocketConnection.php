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
use Hyperf\WebSocketClient\Client;
use Hyperf\WebSocketClient\ClientFactory;
use Lysice\HyperfRedisLock\RedisLock;


class WebsocketConnection implements WebsocketConnectionInterface
{
    protected ?Client $client = null;

    protected array $message = [];
    protected int $createdAt = 0;

    private bool $authenticated = false;


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
        protected ConfigInterface $config,
        protected string        $host,
        protected string        $token,
        protected int           $connectionTimeout = 600,
    )
    {
        $this->reconnect();
    }


    protected function authenticated(): bool
    {
        while (true) {
            
            $this->retryRecvMessage();

            $this->handleAction();

            if($this->isOnHallLogin()){

                return  $this->authenticated = true;
            }
            Coroutine::sleep(0.1); // 添加间隔防止CPU空转
        }
    }

    public function isAuthenticated(): bool
    {
        return $this->authenticated;
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
    public function recv(int $timeout = 10): array
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

            throw $e;
        }
    }

    /**
     * @return array
     * @throws WebSocketTimeOutException
     * @throws WebSocketTokenExpiredException
     */
    public function retryRecvMessage(): array
    {
        $maxRetries = 3;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                return $this->recv();
            } catch (WebSocketTimeOutException $e) {
                if ($attempt === $maxRetries) {
                    throw $e;
                }

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
        $this->createdAt = time();
        $this->client = $this->clientFactory->create($this->host);
        $this->authenticated = false;

        $this->authenticated();

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