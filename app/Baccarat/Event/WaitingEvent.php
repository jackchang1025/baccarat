<?php

namespace App\Baccarat\Event;

use App\Baccarat\Service\LotteryResult;

class WaitingEvent
{

    public function __construct(public LotteryResult $lotteryResult)
    {

    }
}