<?php

namespace App\Baccarat\Service\Websocket;

use App\Baccarat\Service\Exception\WebSocketConnectClosedException;
use App\Baccarat\Service\Exception\WebSocketTimeOutException;
use App\Baccarat\Service\Exception\WebSocketTokenExpiredException;
use App\Baccarat\Service\Output\Output;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Engine\Contract\ChannelInterface;
use RuntimeException;


class WebsocketClient
{

    /**
     * @param Output $output
     * @param Connection $connection
     * @param ChannelInterface $channel
     * @param float $sleepTime
     * @param int $reconnectAttempts
     * @param int $maxRunningTime
     */
    public function __construct(
        protected Output                       $output,
        protected WebSocketConnectionInterface $connection,
        protected ChannelInterface             $channel,
        protected float                        $sleepTime = 0.01,
        protected int                          $reconnectAttempts = 3,
        protected int                          $maxRunningTime = 0,
    )
    {

    }


    /**
     * @return void
     * @throws WebSocketTokenExpiredException
     * @throws \Throwable
     */
    public function startRecvMessage(): void
    {
        $startTime = microtime(true);

        while (true) {

            try {

                if ($this->maxRunningTime > 0 && (microtime(true) - $startTime) >= $this->maxRunningTime) {
                    throw new RuntimeException("Maximum runtime of {$this->maxRunningTime} seconds exceeded");
                }

                if ($this->check()) {
                    $this->retryReconnect();
                }

               $this->connection->retryRecvMessage();

            } catch (WebSocketTimeOutException $e) {

                $this->retryReconnect();
                $this->output->error($e->getMessage());
            }
        }
    }

    /**
     * @param float $retryInterval
     * @return bool
     * @throws WebSocketConnectClosedException
     * @throws WebSocketTimeOutException
     * @throws WebSocketTokenExpiredException
     */
    public function retryReconnect(float $retryInterval = 0.02): bool
    {
        for ($attempt = 1; $attempt <= $this->reconnectAttempts; $attempt++) {
            try {
                return $this->reconnect();
            } catch (WebSocketTimeOutException|WebSocketConnectClosedException $e) {
                if ($attempt === $this->reconnectAttempts) {
                    throw $e; // Re-throw the exception after max reconnect attempts
                }

                Coroutine::sleep($retryInterval);
            }
        }

        throw new WebSocketTimeOutException('Failed to receive message after retries');
    }

    /**
     * @return bool
     * @throws WebSocketTimeOutException
     * @throws WebSocketTokenExpiredException
     * @throws WebSocketConnectClosedException
     */
    public function reconnect(): bool
    {
        $this->output->info("reconnect");
        $this->connection->setIsLoggedIn(false);
        $this->connection->reconnect();
        $this->login();
        return $this->check();
    }

    /**
     * @return bool
     * @throws WebSocketConnectClosedException
     * @throws WebSocketTimeOutException
     * @throws WebSocketTokenExpiredException
     */
    public function login(): bool
    {
        while (!$this->connection->isLoggedIn()){

            $message = $this->connection->retryRecvMessage();

            $this->output->info('receive message: ' . $message->toJson());
        }

        return $this->connection->isLoggedIn();
    }


    protected function check(): bool
    {
        return $this->connection->getRemainingTimeOut() <= random_int(10, 60) || !$this->connection->isLoggedIn();
    }
}