<?php

namespace App\Baccarat\Service\Websocket\Middleware;


use App\Baccarat\Service\Exception\WebSocketConnectClosedException;
use App\Baccarat\Service\Exception\WebSocketTimeOutException;
use App\Baccarat\Service\Exception\WebSocketTokenExpiredException;
use App\Baccarat\Service\Websocket\Message\Message;

#[Middleware(middleware:'validateMessage',group:'WebsocketRecvMessage',priority: 1)]
class ValidateMessageMiddleware
{
    /**
     * @throws WebSocketConnectClosedException
     * @throws WebSocketTokenExpiredException
     * @throws WebSocketTimeOutException
     */
    public function handle(mixed $message, \Closure $next)
    {
        if (!$message instanceof Message) {
            return $next($message);
        }

        if ($message->isEmpty()) {
            throw new WebSocketTimeOutException('Invalid message format');
        }

        if ($message->isTimeoutError()) {
            throw new WebSocketTimeOutException($message->isTimeoutError());
        }

        if ($message->isTokenExpiredError()) {
            throw new WebSocketTokenExpiredException($message->isTokenExpiredError());
        }

        if ($message->isConnectClosedError()) {
            throw new WebSocketConnectClosedException($message->isConnectClosedError());
        }

        return $next($message);
    }
}