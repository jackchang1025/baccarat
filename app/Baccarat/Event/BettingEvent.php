<?php

namespace App\Baccarat\Event;

use App\Baccarat\Service\BaccaratBetting\BaccaratBettingWaitingResult;

class BettingEvent
{

    public function __construct(public BaccaratBettingWaitingResult $baccaratBettingWaitingResult)
    {

    }


}