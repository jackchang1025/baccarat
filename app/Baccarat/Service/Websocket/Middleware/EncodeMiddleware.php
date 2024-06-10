<?php

namespace App\Baccarat\Service\Websocket\Middleware;

use App\Baccarat\Service\Websocket\MessageDecoder\MessageDecoder;

#[Middleware(middleware:'EncodeMiddleware',group:'WebsocketRecvMessage',priority: 0)]
class EncodeMiddleware
{

    public function __construct(protected readonly MessageDecoder $decoder)
    {
    }

    public function handle(mixed $data, \Closure $next)
    {
        if(is_string($data)){
            $data = $this->decoder->decode($data);
        }

        return $next($data);
    }
}