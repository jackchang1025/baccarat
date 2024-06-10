<?php

namespace App\Baccarat\Service\Websocket\MessageDecoder;


use App\Baccarat\Service\Websocket\Message\Message;

class MessageDecoder implements MessageDecoderInterface
{
    public function decode(string $message): Message
    {
        try {
            return new Message(json_decode($message, true, 512, JSON_THROW_ON_ERROR));
        } catch (\JsonException) {
            return new Message();
        }
    }
}