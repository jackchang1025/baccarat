<?php

namespace App\Baccarat\Event;

use App\Baccarat\Service\LotteryResult;

class BettingEvent
{

    public function __construct(public LotteryResult $lotteryResult)
    {
    }
}