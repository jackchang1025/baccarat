<?php

namespace App\Baccarat\Service\Websocket;

use App\Baccarat\Service\Exception\WebSocketConnectClosedException;
use App\Baccarat\Service\Exception\WebSocketTimeOutException;
use App\Baccarat\Service\Exception\WebSocketTokenExpiredException;
use App\Baccarat\Service\Output\Output;
use App\Baccarat\Service\Websocket\Message\Message;
use App\Baccarat\Service\Websocket\MessageDecoder\MessageDecoderInterface;
use App\Baccarat\Service\Websocket\MessageHandler\MessageHandlerInterface;
use App\Baccarat\Service\Websocket\Middleware\MiddlewareManager;
use Hyperf\Coroutine\Coroutine;

class Connection implements WebSocketConnectionInterface
{
    private ConnectionState $state;
    private bool $isLoggedIn = false;

    public function __construct(
        private readonly Output $output,
        private readonly MessageDecoderInterface $messageDecoder,
        private readonly MiddlewareManager $middlewareManager,
        private readonly MessageHandlerInterface $messageHandler,
        private readonly string $host,
        private readonly int $connectionTimeout = 600,
        private readonly int $remainingTimeOut = 30,
    ) {
        $this->state = make(ConnectionState::class,['host'=>$this->host]);
    }

    public function isLoggedIn(): bool
    {
        return $this->isLoggedIn;
    }

    public function setIsLoggedIn(bool $isLoggedIn): void
    {
        $this->isLoggedIn = $isLoggedIn;
    }

    /**
     * @param float $timeout
     * @param int $maxRetries
     * @param float $retryInterval
     * @return Message
     * @throws WebSocketTimeOutException|WebSocketTokenExpiredException|WebSocketConnectClosedException
     */
    public function retryRecvMessage(float $timeout = 10,int $maxRetries = 3, float $retryInterval = 0.02): Message
    {
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                return $this->recv($timeout);
            } catch (WebSocketTimeOutException) {
                if ($attempt === $maxRetries) {
                    throw new WebSocketTimeOutException('Failed to receive message after retries');
                }

                Coroutine::sleep($retryInterval);
            }
        }

        throw new WebSocketTimeOutException('Failed to receive message after retries');
    }

    /**
     * @param float $timeout
     * @return Message
     * @throws WebSocketTimeOutException|WebSocketTokenExpiredException|WebSocketConnectClosedException
     */
    private function recv(float $timeout = 10): Message
    {
        try {
            $msg = $this->state->getClient()->recv($timeout);
            if (!$msg) {
                throw new WebSocketTimeOutException('Failed to receive message or timed out');
            }

            $message = $this->messageDecoder->decode($msg->data);

            $message = $this->middlewareManager->handle('WebsocketRecvMessage',$message);

            $this->messageHandler->handle($this,$message);

            return $message;
        } catch (\Exception $e) {
            $this->output->error('Failed to receive message: ' . $e->getMessage());
            throw $e;
        }
    }

    public function push(array $data, int $opcode = WEBSOCKET_OPCODE_TEXT, ?int $flags = null): bool
    {
        return $this->state->getClient()->push(json_encode($data), $opcode, $flags);
    }

    public function close(): bool
    {
        return $this->state->close();
    }

    public function reconnect(): bool
    {
        return $this->state->reconnect();
    }

    public function getConnection(): ConnectionState
    {
        return $this->state;
    }

    public function getRemainingTimeOut(): int
    {
        return $this->state->getRemainingTimeOut($this->connectionTimeout);
    }

    public function check(): bool
    {
        return !$this->isLoggedIn()
            || $this->state->isTimeOut($this->connectionTimeout)
            || $this->state->getRemainingTimeOut($this->connectionTimeout) <= $this->remainingTimeOut;
    }

    public function release(): void
    {
        $this->state->close();
    }
}