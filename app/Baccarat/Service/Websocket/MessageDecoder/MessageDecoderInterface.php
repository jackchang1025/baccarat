<?php

namespace App\Baccarat\Service\Websocket\MessageDecoder;

use App\Baccarat\Service\Websocket\Message\Message;

interface MessageDecoderInterface
{
    public function decode(string $message): Message;

}