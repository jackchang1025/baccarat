<?php

namespace App\Baccarat\Event;

use App\Baccarat\Service\LotteryResult;
use Hyperf\Collection\Collection;

class RecvMessageEvent
{

    public function __construct(public LotteryResult $lotteryResult)
    {
    }
}