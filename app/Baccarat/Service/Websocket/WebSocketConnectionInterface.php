<?php

namespace App\Baccarat\Service\Websocket;

use App\Baccarat\Service\Websocket\Message\Message;
use Hyperf\Contract\ConnectionInterface;

interface WebSocketConnectionInterface extends ConnectionInterface
{
//    public function isTimeout():bool;
//
//    public function getCreatedAt():int;
//
    public function getRemainingTimeOut():int;

    public function isLoggedIn():bool;

    public function retryRecvMessage():Message;

}