<?php

namespace App\Baccarat\Event;


use App\Baccarat\Service\BaccaratBetting\BaccaratBettingWaitingResult;

class WaitingEvent
{

    public function __construct(public readonly BaccaratBettingWaitingResult $baccaratBettingWaitingResult)
    {

    }
}